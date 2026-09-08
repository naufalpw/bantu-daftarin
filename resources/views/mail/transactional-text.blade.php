BantuDaftarin

{{ $greeting }}

{{ $title }}

@foreach ($paragraphs as $paragraph)
{{ $paragraph }}

@endforeach
@if ($otpCode !== null)
{{ $otpCode }}

@endif
@foreach ($context as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@if ($context !== [])

@endif
@if (! empty($actionText) && ! empty($actionUrl))
{{ $actionText }}: {{ $actionUrl }}

@endif
@foreach ($secondaryLines as $line)
{{ $line }}

@endforeach
Email transaksi otomatis dari BantuDaftarin.
