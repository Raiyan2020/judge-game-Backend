<form method="POST" action="{{ route('admin.banners.changeStatus', $banner) }}">
    @csrf
    @method('PUT')
    <button type="submit" class="btn btn-{{ getClass($banner->is_active) }}">{{ getStatusName($banner->is_active) }}</button>
</form>
