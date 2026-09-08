<header class="bd-admin-header">
    <button class="bd-admin-menu-button" type="button" data-admin-drawer-open aria-controls="admin-navigation" aria-expanded="false">
        <span class="bd-admin-menu-button__icon" aria-hidden="true"><i></i><i></i><i></i></span>
        <span>Menu</span>
    </button>

    <div class="bd-admin-header__context">
        <span>ADMINISTRASI</span>
        <strong>{{ trim($__env->yieldContent('admin_context')) ?: 'Ruang kerja admin' }}</strong>
    </div>

    <div class="bd-admin-header__identity">
        <span class="bd-admin-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <span><strong>{{ auth()->user()->name }}</strong><small>Super Admin</small></span>
    </div>
</header>
