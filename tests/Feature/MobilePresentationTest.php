<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationStatusHistory;
use App\Models\Document;
use App\Models\Service;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MobilePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_queues_keep_one_record_action_and_the_same_evidence_context(): void
    {
        $admin = $this->admin();
        $application = Application::factory()->create(['status' => ApplicationStatus::UNDER_REVIEW]);
        $requirement = ApplicationRequirement::factory()->create(['application_id' => $application->id]);
        Document::factory()->create(['application_id' => $application->id, 'application_requirement_id' => $requirement->id]);

        foreach (['admin.applications.index' => '', 'admin.documents.index' => '#documents-title'] as $route => $anchor) {
            $response = $this->actingAs($admin)->get(route($route))->assertOk();
            $xpath = $this->html($response->getContent());
            $href = route('admin.applications.show', $application->public_id).$anchor;
            $this->assertSame(1, $xpath->query('//table//a[@href="'.$href.'"]')->length);
            $row = $xpath->query('//table//a[@href="'.$href.'"]/ancestor::tr')->item(0);
            $this->assertNotNull($row);
            $this->assertStringContainsString($application->service->name, $row->textContent);
            $this->assertStringContainsString($application->user->name, $row->textContent);
        }
    }

    public function test_admin_detail_keeps_single_review_forms_and_private_evidence_links(): void
    {
        $admin = $this->admin();
        $application = Application::factory()->create(['status' => ApplicationStatus::UNDER_REVIEW]);
        $requirement = ApplicationRequirement::factory()->create(['application_id' => $application->id]);
        $document = Document::factory()->create(['application_id' => $application->id, 'application_requirement_id' => $requirement->id]);
        $response = $this->actingAs($admin)->get(route('admin.applications.show', $application->public_id))->assertOk();
        $xpath = $this->html($response->getContent());

        foreach ([route('admin.documents.review', $document->public_id), route('admin.applications.review.finalize', $application->public_id)] as $action) {
            $this->assertSame(1, $xpath->query('//form[@method="post" and @action="'.$action.'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//a[@href="'.route('admin.documents.view', $document->public_id).'" and @target="_blank"]')->length);
        $this->assertSame(0, $xpath->query('//iframe')->length);
        $this->assertSame(1, $xpath->query('//*[@id="admin-next-action"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="history-title"]/ancestor::details[contains(concat(" ", normalize-space(@class), " "), " bd-admin-detail-section--history ")]')->length);
    }

    public function test_client_workspace_keeps_one_upload_per_requirement_and_working_history_target(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::DRAFT]);
        $requirements = collect(['KTP', 'FOTO_WAJAH'])->map(fn (string $code) => ApplicationRequirement::factory()->create(['application_id' => $application->id, 'code' => $code]));
        ApplicationStatusHistory::create(['application_id' => $application->id, 'to_status' => ApplicationStatus::DRAFT, 'actor_type' => 'client', 'created_at' => now()]);
        $response = $this->actingAs($user)->get(route('client.applications.show', $application->public_id))->assertOk();
        $xpath = $this->html($response->getContent());

        foreach ($requirements as $requirement) {
            $form = '//form[@action="'.route('client.documents.store', [$application->public_id, $requirement->public_id]).'"]';
            $this->assertSame(1, $xpath->query($form)->length);
            $this->assertSame(1, $xpath->query($form.'//input[@type="file" and @name="file"]')->length);
        }
        foreach (['ringkasan', 'proses', 'client-help-unread'] as $id) {
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//*[@*[name()="wire:poll.12s" and .="poll"]]')->length);
    }

    public function test_upload_validation_feedback_is_local_to_the_submitted_requirement(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::DRAFT]);
        $requirement = ApplicationRequirement::factory()->create(['application_id' => $application->id, 'code' => 'KTP']);
        $key = 'personal-document-'.$requirement->public_id;
        $this->actingAs($user)->withSession([
            '_old_input' => ['_ui_form' => $key],
            'errors' => (new ViewErrorBag)->put('default', new MessageBag(['file' => 'File sintetis tidak valid.'])),
        ]);
        $response = $this->get(route('client.applications.show', $application->public_id))->assertOk();
        $xpath = $this->html($response->getContent());
        $feedback = $xpath->query('//*[@id="feedback-'.$key.'"]');
        $this->assertSame(1, $feedback->length);
        $this->assertStringContainsString('File sintetis tidak valid.', $feedback->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//form[@action="'.route('client.documents.store', [$application->public_id, $requirement->public_id]).'"]//*[@id="feedback-'.$key.'"]')->length);
    }

    public function test_client_phone_navigation_keeps_four_labeled_icon_destinations_without_duplicate_actions(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('client.dashboard'))->assertOk();
        $xpath = $this->html($response->getContent());
        $links = $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " pb-bottom-nav ")]/a');

        $this->assertSame(4, $links->length);
        $this->assertSame(0, $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " pb-bottom-nav ")]//form')->length);

        foreach (['Beranda', 'Layanan', 'Pengajuan', 'Bantuan'] as $index => $label) {
            $this->assertStringContainsString($label, $links->item($index)->textContent);
            $this->assertSame(1, $xpath->query('.//*[local-name()="svg" and @aria-hidden="true"]', $links->item($index))->length);
        }
    }

    public function test_client_desktop_navigation_reuses_the_four_authorized_destinations_with_shared_icons(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('client.dashboard'))->assertOk();
        $xpath = $this->html($response->getContent());
        $links = $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " pb-desktop-nav ")]/a');
        $destinations = [
            'Beranda' => route('client.dashboard'),
            'Layanan' => route('client.services.index'),
            'Pengajuan' => route('client.applications.index'),
            'Bantuan' => route('qna'),
        ];

        $this->assertSame(4, $links->length);
        $this->assertSame(0, $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " pb-desktop-nav ")]//form')->length);

        foreach (array_values($destinations) as $index => $href) {
            $link = $links->item($index);
            $this->assertSame($href, $link->getAttribute('href'));
            $this->assertStringContainsString(array_keys($destinations)[$index], $link->textContent);
            $this->assertSame(1, $xpath->query('.//*[local-name()="svg" and contains(concat(" ", normalize-space(@class), " "), " bd-ui-icon ")]', $link)->length);
        }
    }

    public function test_admin_sidebar_keeps_six_destinations_while_desktop_icons_share_one_svg_language(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
        $xpath = $this->html($response->getContent());
        $links = $xpath->query('//*[@id="admin-navigation"]//nav//a');

        $this->assertSame(6, $links->length);
        $this->assertSame(0, $xpath->query('//*[@id="admin-navigation"]//nav//form')->length);

        foreach ($links as $link) {
            $this->assertSame(1, $xpath->query('.//*[local-name()="svg" and contains(concat(" ", normalize-space(@class), " "), " bd-ui-icon ")]', $link)->length);
            $this->assertSame(1, $xpath->query('.//img[@aria-hidden="true"]', $link)->length);
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);

        return $user;
    }

    private function html(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
