<?php

namespace App\Livewire;

use App\Enums\BusinessType;
use App\Models\Application;
use App\Services\ApplicationWorkflowService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ApplicationDetailsForm extends Component
{
    private const PERSONAL_SELECT_VALUES = [
        'gender' => ['Pria', 'Wanita'],
        'marital_status' => ['Lajang', 'Kawin', 'Cerai Hidup', 'Cerai Mati'],
        'family_status' => ['Suami', 'Istri', 'Anak'],
    ];

    private const LEGACY_PERSONAL_SELECT_VALUES = [
        'gender' => [
            'Laki-Laki' => 'Pria',
            'Laki-laki' => 'Pria',
            'Perempuan' => 'Wanita',
        ],
        'marital_status' => [
            'Belum Menikah' => 'Lajang',
            'Menikah' => 'Kawin',
        ],
        'family_status' => [],
    ];

    public string $applicationId;

    public string $kind = '';

    public array $details = [];

    public array $representative = ['name' => '', 'relationship' => '', 'email' => ''];

    public array $additionalRepresentative = ['name' => '', 'relationship' => '', 'email' => ''];

    public string $saveState = '';

    public string $variant = 'default';

    public function mount(Application $application, string $variant = 'default'): void
    {
        Gate::authorize('update', $application);
        $this->variant = $variant;
        $application->load(['service', 'personalDetails', 'businessDetails', 'representatives']);
        $this->applicationId = $application->public_id;
        $this->kind = $application->service->code;

        if ($application->personalDetails) {
            $this->details = $this->normalizeLegacyPersonalSelectValues([
                'name' => $application->personalDetails->name,
                'nik' => $application->personalDetails->nik,
                'family_card_number' => $application->personalDetails->family_card_number,
                'email' => $application->personalDetails->email,
                'marital_status' => $application->personalDetails->marital_status,
                'family_status' => $application->personalDetails->family_status,
                'purpose' => $application->personalDetails->purpose,
                'gender' => $application->personalDetails->gender,
            ]);
        } else {
            $this->details = [
                'business_name' => $application->businessDetails?->business_name,
                'business_type' => $application->businessDetails?->business_type,
                'business_type_other' => $application->businessDetails?->business_type_other,
                'purpose' => $application->businessDetails?->purpose,
            ];
            $primary = $application->representatives->firstWhere('is_primary', true);
            $additional = $application->representatives->firstWhere('is_primary', false);
            if ($primary) {
                $this->representative = ['name' => $primary->name, 'relationship' => $primary->relationship->value, 'email' => $primary->email];
            }
            if ($additional) {
                $this->additionalRepresentative = ['name' => $additional->name, 'relationship' => $additional->relationship->value, 'email' => $additional->email];
            }
        }
    }

    public function save(): void
    {
        $application = Application::query()
            ->where('public_id', $this->applicationId)
            ->where('user_id', auth()->id())
            ->with('service')
            ->firstOrFail();
        Gate::authorize('update', $application);
        $kind = $application->service->code;
        if ($kind === 'NPWP_PERSONAL') {
            $this->details = $this->normalizeLegacyPersonalSelectValues($this->details);
        }
        $validated = $this->validate($this->rules($kind));

        app(ApplicationWorkflowService::class)->saveDetails(
            $application,
            $validated['details'],
            $kind === 'NPWP_BUSINESS' ? $validated['representative'] : null,
            auth()->user(),
            $kind === 'NPWP_BUSINESS' ? $validated['additionalRepresentative'] : null,
        );
        $this->kind = $kind;
        $this->saveState = 'Tersimpan otomatis.';
    }

    protected function rules(string $kind): array
    {
        $rules = [
            'details.email' => ['nullable', 'email:rfc', 'max:190'],
            'details.marital_status' => ['nullable', 'string', Rule::in(self::PERSONAL_SELECT_VALUES['marital_status'])],
            'details.family_status' => ['nullable', 'string', Rule::in(self::PERSONAL_SELECT_VALUES['family_status'])],
            'details.gender' => ['nullable', 'string', Rule::in(self::PERSONAL_SELECT_VALUES['gender'])],
            'details.purpose' => ['nullable', 'string', 'max:255'],
        ];

        if ($kind === 'NPWP_PERSONAL') {
            $rules['details.name'] = ['required', 'string', 'max:120'];
            $rules['details.nik'] = ['nullable', 'digits:16'];
            $rules['details.family_card_number'] = ['nullable', 'digits:16'];
        } else {
            $rules['details.business_name'] = ['required', 'string', 'max:190'];
            $rules['details.business_type'] = ['nullable', 'string', Rule::in(BusinessType::values())];
            $rules['details.business_type_other'] = ['nullable', 'required_if:details.business_type,OTHER', 'prohibited_unless:details.business_type,OTHER', 'string', 'max:128'];
            $rules['representative.name'] = ['required', 'string', 'max:120'];
            $rules['representative.relationship'] = ['required', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'];
            $rules['representative.email'] = ['nullable', 'email:rfc', 'max:190'];
            $rules['additionalRepresentative.name'] = ['nullable', 'required_with:additionalRepresentative.relationship,additionalRepresentative.email', 'string', 'max:120'];
            $rules['additionalRepresentative.relationship'] = ['nullable', 'required_with:additionalRepresentative.name,additionalRepresentative.email', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'];
            $rules['additionalRepresentative.email'] = ['nullable', 'email:rfc', 'max:190'];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.application-details-form');
    }

    private function normalizeLegacyPersonalSelectValues(array $details): array
    {
        foreach (self::LEGACY_PERSONAL_SELECT_VALUES as $field => $aliases) {
            $value = $details[$field] ?? null;

            if (is_string($value) && array_key_exists($value, $aliases)) {
                $details[$field] = $aliases[$value];
            }
        }

        return $details;
    }
}
