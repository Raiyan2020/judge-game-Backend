<form method="POST" action="{{ route('admin.packages.changeStatus', $package) }}">
    @csrf
    @method('PUT')
    <button type="submit"
        class="btn btn-{{ getClass($package->is_active) }}">{{ getStatusName($package->is_active) }}</button>
</form>
