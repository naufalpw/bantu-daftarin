@extends('layouts.marketing')

@section('body_class', 'bd-registration-body')

@section('content')
<div class="bd-registration-page bd-business-type-page" data-node-id="208:20911" data-name="Desktop">
    <x-site-header />

    <main class="bd-registration-content">
        <div class="bd-registration-back-wrap" data-node-id="208:20913">
            <a class="bd-registration-back" href="{{ route('client.services.index') }}" data-node-id="208:20914">
                <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
                <span>Kembali</span>
            </a>
        </div>

        <section class="bd-business-type-intro" data-node-id="208:20917">
            <div class="bd-business-type-intro__copy">
                <div class="bd-business-type-intro__icon" data-node-id="208:20920" aria-hidden="true">
                    <span class="bd-building-icon__side bd-building-icon__side--left"></span>
                    <span class="bd-building-icon__side bd-building-icon__side--right"></span>
                    <span class="bd-building-icon__center"></span>
                    <span class="bd-building-icon__base"></span>
                </div>
                <div>
                    <h1 data-node-id="208:20921">PilIh Jenis badan Usaha</h1>
                    <p data-node-id="208:20919">Pilih jenis usaha yang sesuai dengan usaha<br>Anda untuk melanjutka n pendaftaran NPWP</p>
                </div>
            </div>
        </section>

        <section class="bd-business-type-card" data-node-id="208:20922">
            <div class="bd-business-type-grid" data-node-id="208:20923">
                @foreach(array_chunk($businessTypes, 5) as $column)
                    <div class="bd-business-type-column">
                        @foreach($column as $type)
                            <a class="bd-business-type-option" href="{{ route('client.applications.create', ['service' => $businessService->public_id, 'business_type' => $type->value]) }}" data-business-type="{{ $type->value }}">
                                <span>{{ $type->label() }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</div>
@endsection
