
<form method="POST" action="{{ route('admin.countries.changeStatus', $country) }}"  >
    @csrf
    @method('PUT')
<button type="submit" class="btn btn-{{ getClass($country->is_active) }}">{{  getStatusName($country->is_active)}}</button>
</form>