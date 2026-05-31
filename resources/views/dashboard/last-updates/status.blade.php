<form method="POST" action="{{ route('admin.last-updates.changeStatus', $lastUpdate) }}">
    @csrf
    @method('PUT')
    <button type="submit" class="btn btn-{{ getClass($lastUpdate->is_active) }}">{{ getStatusName($lastUpdate->is_active) }}</button>
</form>
