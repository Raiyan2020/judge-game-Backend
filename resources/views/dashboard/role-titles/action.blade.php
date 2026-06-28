<a class="btn btn-warning btn-icon"
   href="{{ route('admin.role-titles.edit', $id) }}">
    <i class="fa fa-pencil"></i></a>
<a class="btn btn-danger btn-icon" onclick="delete_form(this)"
   data-href="{{ route('admin.role-titles.destroy', $id) }}">
    <i class="fa fa-trash white"></i></a>
