<a href="/dashboard" class="item {{ request()->is('dashboard') ? 'active' : ''}}">
    <div class="col">
        <ion-icon name="home-outline" role="img" class="md hydrated"
            aria-label="home full outline"></ion-icon>
        <strong>Home</strong>
    </div>
</a>
<a href="/jadwal" class="item {{ request()->is('jadwal*') ? 'active' : ''}}">
    <div class="col">
        <ion-icon name="calendar-outline" role="img" class="md hydrated"
            aria-label="calendar outline"></ion-icon>
        <strong>Schedule</strong>
    </div>
</a>
<a href="/attendance" class="item">
    <div class="col">
        <div class="action-button large">
            <ion-icon name="camera" role="img" class="md hydrated" aria-label="add outline"></ion-icon>
        </div>
    </div>
</a>
<a href="/cuti" class="item {{ request()->is('cuti*') ? 'active' : ''}}">
    <div class="col">
        <ion-icon name="document-text-outline" role="img" class="md hydrated"
            aria-label="document text outline"></ion-icon>
        <strong>Cuti</strong>
    </div>
</a>
<a href="/profile" class="item {{ request()->is('profile*') ? 'active' : ''}}">
    <div class="col">
        <ion-icon name="people-outline" role="img" class="md hydrated" aria-label="people outline"></ion-icon>
        <strong>Profile</strong>
    </div>
</a>
