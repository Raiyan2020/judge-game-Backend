<div class="admin-table-actions">
<a class="btn btn-info btn-icon"
   href="{{ route('admin.users.show', $id) }}">
    <i class="fa fa-eye"></i></a>
<a class="btn btn-danger btn-icon" onclick="delete_form(this)"
   data-href="{{ route('admin.users.destroy', $id) }}">
    <i class="fa fa-trash white"></i></a>
</div>
