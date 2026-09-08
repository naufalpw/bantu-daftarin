<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessRelationship;
use App\Enums\BusinessType;
use App\Models\Application;
use App\Models\PersonalApplicationDetail;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_business_type_route_redirects_to_start_flow_with_all_canonical_choices(): void
    {
        $service = Service::factory()->create(['code' => 'NPWP_BUSINESS']);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'AKTA_NOTARIS']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('npwp.business.types'));

        $response->assertRedirect(route('client.applications.create', $service->public_id));
        $this->actingAs($user)
            ->get(route('client.applications.create', $service->public_id))
            ->assertOk()
            ->assertSee('Badan Internasional')
            ->assertSee('Perseroan Komanditer (CV)')
            ->assertSee('LAINNYA');
        $this->assertCount(25, BusinessType::cases());
    }

    public function test_standard_business_type_is_stored_as_a_canonical_value(): void
    {
        $user = User::factory()->create();
        $service = $this->businessService();

        $response = $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'PT Synthetic Canonical',
            'business_type' => BusinessType::LIMITED_LIABILITY_COMPANY->value,
            'representative' => [
                'name' => 'Synthetic Primary',
                'relationship' => BusinessRelationship::DIRECTOR->value,
            ],
        ]);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('client.applications.show', $application->public_id));
        $this->assertSame(BusinessType::LIMITED_LIABILITY_COMPANY->value, $application->businessDetails->business_type);
        $this->assertNull($application->businessDetails->business_type_other);
    }

    public function test_other_business_type_requires_and_stores_the_controlled_custom_value(): void
    {
        $user = User::factory()->create();
        $service = $this->businessService();
        $base = [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'Synthetic Other Entity',
            'business_type' => BusinessType::OTHER->value,
            'representative' => [
                'name' => 'Synthetic Primary',
                'relationship' => BusinessRelationship::OWNER->value,
            ],
        ];

        $this->actingAs($user)->post(route('client.applications.store'), $base)->assertSessionHasErrors('business_type_other');
        $this->assertDatabaseCount('applications', 0);

        $response = $this->actingAs($user)->post(route('client.applications.store'), $base + ['business_type_other' => 'Badan Usaha Sintetis']);

        $response->assertSessionHasNoErrors();
        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(BusinessType::OTHER->value, $application->businessDetails->business_type);
        $this->assertSame('Badan Usaha Sintetis', $application->businessDetails->business_type_other);
    }

    public function test_business_type_other_is_not_retained_for_a_standard_type(): void
    {
        $user = User::factory()->create();
        $service = $this->businessService();

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'Synthetic Standard Entity',
            'business_type' => BusinessType::LIMITED_LIABILITY_COMPANY->value,
            'business_type_other' => 'Should not persist',
            'representative' => [
                'name' => 'Synthetic Primary',
                'relationship' => BusinessRelationship::DIRECTOR->value,
            ],
        ])->assertSessionHasErrors('business_type_other');
    }

    public function test_nik_and_family_card_number_are_nullable_for_draft_but_required_before_submit(): void
    {
        $user = User::factory()->create();
        $service = $this->personalService();

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_PERSONAL',
            'consent' => 1,
            'name' => 'Synthetic Personal Identity',
        ])->assertSessionHasNoErrors();
        $application = Application::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->post(route('client.applications.submit', $application->public_id))
            ->assertSessionHasErrors('error');
        $this->assertSame(ApplicationStatus::DRAFT, $application->fresh()->status);

        $this->actingAs($user)->put(route('client.applications.update', $application->public_id), [
            'nik' => '123',
            'family_card_number' => '456',
        ])->assertSessionHasErrors(['nik', 'family_card_number']);
    }

    public function test_optional_npwp_requirement_does_not_block_personal_submission(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KTP', 'is_required' => true]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'NPWP', 'is_required' => false]);

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_PERSONAL',
            'consent' => 1,
            'name' => 'Synthetic Optional NPWP',
            'nik' => '3173055501010001',
            'family_card_number' => '3173055501010002',
        ])->assertSessionHasNoErrors();
        $application = Application::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertFalse($application->requirements->firstWhere('code', 'NPWP')->is_required);
        $this->actingAs($user)->post(route('client.applications.submit', $application->public_id))->assertRedirect();
        $this->assertSame(ApplicationStatus::AWAITING_DOCUMENTS, $application->fresh()->status);
    }

    public function test_identity_fields_use_encrypted_storage_casts(): void
    {
        $application = Application::factory()->create();
        $detail = PersonalApplicationDetail::create([
            'application_id' => $application->id,
            'name' => 'Synthetic Encrypted Identity',
            'nik' => '3173055501010001',
            'family_card_number' => '3173055501010002',
        ]);

        $rawNik = DB::table('personal_application_details')->where('id', $detail->id)->value('nik');

        $this->assertNotSame('3173055501010001', $rawNik);
        $this->assertSame('3173055501010001', $detail->fresh()->nik);
        $this->assertSame('3173055501010002', $detail->fresh()->family_card_number);
    }

    public function test_business_page_maps_figma_names_and_keeps_conditional_documents(): void
    {
        $user = User::factory()->create();
        $service = $this->businessService();
        foreach ([
            ['code' => 'KTP_PENANGGUNG_JAWAB', 'name' => 'KTP Penanggung Jawab', 'is_required' => true],
            ['code' => 'SK_AHU', 'name' => 'SK AHU', 'is_required' => true],
            ['code' => 'SURAT_KUASA', 'name' => 'Surat Kuasa', 'is_required' => false, 'condition' => 'jika diwakilkan'],
        ] as $item) {
            ServiceRequirement::factory()->create(['service_id' => $service->id] + $item);
        }

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'Synthetic Representative Entity',
            'business_type' => BusinessType::LIMITED_LIABILITY_COMPANY->value,
            'representative' => [
                'name' => 'Synthetic Primary Representative',
                'relationship' => BusinessRelationship::DIRECTOR->value,
            ],
            'additional_representative' => [
                'name' => 'Synthetic Additional Representative',
                'relationship' => BusinessRelationship::EMPLOYEE->value,
            ],
        ])->assertSessionHasNoErrors();
        $application = Application::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Penanggung jawab utama')
            ->assertSee('Penanggung jawab tambahan')
            ->assertSee('KTP Penanggung Jawab')
            ->assertSee('Surat Kuasa');
        $this->assertSame('Synthetic Primary Representative', $application->representatives->firstWhere('is_primary', true)->name);
        $this->assertSame('Synthetic Additional Representative', $application->representatives->firstWhere('is_primary', false)->name);
    }

    public function test_registration_page_cannot_be_read_by_another_client(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = $this->personalService();
        $application = Application::factory()->create([
            'user_id' => $owner->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);

        $this->actingAs($other)->get(route('npwp.personal.application', $application->public_id))->assertNotFound();
    }

    private function personalService(): Service
    {
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KTP']);

        return $service;
    }

    private function businessService(): Service
    {
        $service = Service::factory()->create(['code' => 'NPWP_BUSINESS']);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'AKTA_NOTARIS']);

        return $service;
    }
}
