<form method="POST" action="{{ route('admin.tips.changeStatus', $tip) }}">
    @csrf
    @method('PUT')
    <button type="submit" class="btn btn-{{ getClass($tip->is_active) }}">{{ getStatusName($tip->is_active) }}</button>
</form>
