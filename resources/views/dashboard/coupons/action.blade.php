<div class="admin-table-actions">
<a class="btn btn-info btn-icon"
   href="{{ route('admin.coupons.show', $id) }}">
    <i class="fa fa-eye"></i></a>
<a class="btn btn-warning btn-icon"
   href="{{ route('admin.coupons.edit', $id) }}">
    <i class="fa fa-pencil"></i></a>
<a class="btn btn-danger btn-icon" onclick="delete_form(this)"
   data-href="{{ route('admin.coupons.destroy', $id) }}">
    <i class="fa fa-trash white"></i></a>
</div>
