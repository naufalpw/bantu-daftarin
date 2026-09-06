@props(['kicker', 'title', 'description' => null])

<header class="bd-admin-page-header">
    <div>
        <p class="bd-admin-kicker">{{ $kicker }}</p>
        <h1>{{ $title }}</h1>
        @if($description)<p>{{ $description }}</p>@endif
    </div>
    @if(trim($slot))<div class="bd-admin-page-header__actions">{{ $slot }}</div>@endif
</header>
