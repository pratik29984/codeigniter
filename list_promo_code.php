<div class="x_title">
    <h2><i class="fa fa-tag"></i> Manage Promo Code</h2>
    <h2 style="float:right"><a class="btn btn-success btn-xs" href="<?php echo base_url(); ?>admin/add_promo_code"><i class="fa fa-plus"></i> Add New Promo Code</a></h2>
    <div class="clearfix"></div>
</div>
<div class="x_content">
    <div class="table-responsive">
        <table id="table" class="table table-striped table-bordered bulk_action">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Promo Code</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Valid From</th>
                    <th>Valid To</th>
                    <th>Max Usage</th>
                    <th>No. of Usage</th>
                    <th>Max Usage Per Customer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<form name="delete_promo_code" method="post" action="<?php echo base_url('admin/delete_promo_code'); ?>">
    <input type="hidden" name="delete_id" value="">
</form>
<script>
    $(document).ready(function ()
    {
        var dataTable = $('#table').DataTable( {
            "processing": true,
            "serverSide": true,
            "ajax":{
                url :'<?php echo base_url("admin/get_promo_code")?>',
                type: "post", 
            },
            "aoColumns": [
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": true, "bSortable": true},
            {"bVisible": true, "bSearchable": false, "bSortable": false},
            ]
        });
    });

    function delete_promo_code(id)
    {
        swal({
            title: "Are you sure?",
            text: "Do you want to delete promo code",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        },
        function () {
            $('input[name=delete_id]').val(id);
            $('form[name=delete_promo_code]').submit();
        });
    }
</script>