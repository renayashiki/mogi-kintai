<header class="header">
    <div class="header-inner">
        <div class="header-logo">
            <a href="{{ route('attendance.index') }}">
                @include('components.logo-svg')
            </a>
        </div>
        <nav class="header-nav">
            <ul class="nav-list">
                {{-- $attendanceStatusが未定義、または'finished'（退勤済）以外の場合は通常メニューを表示 --}}
                @if (($attendanceStatus ?? 'outside') !== 'finished')
                    <li class="nav-item">
                        <a href="{{ route('attendance.index') }}" class="nav-link">勤怠</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('attendance.list') }}" class="nav-link">勤怠一覧</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('attendance.request.list') }}" class="nav-link">申請</a>
                    </li>
                @else
                    {{-- 退勤済の場合のみ、ボタンの内容を切り替える --}}
                    <li class="nav-item">
                        <a href="{{ route('attendance.list') }}" class="nav-link">今月の出勤一覧</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('attendance.request.list') }}" class="nav-link">申請一覧</a>
                    </li>
                @endif
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
