<div class="nav flex-column nav-pills">
    <a href="{{ route('admin.activity.index') }}" class="nav-link {{ active([route('admin.activity.index')]) }}"><i class="fas fa-fw fa-stream"></i> Logs</a>
    <a href="{{ route('admin.console.index') }}" class="nav-link {{ active([route('admin.console.index')]) }}"><i class="fas fa-fw fa-terminal"></i> Console</a>

    @if (user()->hasRole('admin'))
    @endif
</div>