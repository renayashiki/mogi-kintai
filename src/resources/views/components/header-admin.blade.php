<header class="header">
    <div class="header-inner">
        <div class="header-logo">
            <a href="/admin/attendance/list">
                @include('components.logo-svg')
            </a>
        </div>
        <nav class="header-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="{{ route('admin.attendance.list') }}" class="nav-link">勤怠一覧</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('staff.list') }}" class="nav-link">スタッフ一覧</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('attendance.request.list') }}" class="nav-link">申請一覧</a>
                </li>
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="post" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-button">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
