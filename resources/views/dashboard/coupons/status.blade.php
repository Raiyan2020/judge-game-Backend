<form method="POST" action="{{ route('admin.coupons.changeStatus', $coupon) }}">
    @csrf
    @method('PUT')
    <button type="submit" class="btn btn-{{ getClass($coupon->is_active) }}">{{ getStatusName($coupon->is_active) }}</button>
</form>
