<div class="x_title">
    <h2><?php echo $Title; ?></h2>
    <div class="clearfix"></div>
</div>

<div class="x_content">
    <form class="form-horizontal form-label-left validate" action="" method="post" name="registration"  enctype='multipart/form-data' id="package">
        <div class="col-md-12 col-xs-12">
            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="promo_code">Promo Code <span class="required">*</span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="text" class="form-control" value="<?php echo set_value('promo_code', isset($data->promo_code)?$data->promo_code:""); ?>" name="promo_code" required="">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="type">Type <span class="required">*</span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <select name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="flat" <?php echo set_select('type', "$", (!empty($data) && $data->type == "flat" ? TRUE : FALSE)); ?>>Flat</option>
                        <option value="percentage" <?php echo set_select('type', "$", (!empty($data) && $data->type == "percentage" ? TRUE : FALSE)); ?>>Percentage</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="amount">Amount <span class="required">*</span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="number" class="form-control" value="<?php echo set_value('amount', isset($data->amount)?$data->amount:""); ?>" name="amount" required="">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="valid_from">Valid from <span class="required">*</span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="text" class="form-control" value="<?php echo set_value('valid_from', isset($data->valid_from)?date('d-m-Y', strtotime($data->valid_from)):""); ?>" name="valid_from" id="valid_from" required="">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="valid_to">Valid to <span class="required">*</span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="text" class="form-control" value="<?php echo set_value('valid_to', isset($data->valid_to)?date('d-m-Y', strtotime($data->valid_to)):""); ?>" name="valid_to" id="valid_to" required="">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="max_usage">Max Usage (Blank For Unlimited) <span class="required"></span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="text" class="form-control" value="<?php echo set_value('max_usage', isset($data->max_usage)?$data->max_usage:""); ?>" name="max_usage">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4 col-sm-4 col-xs-12" for="max_usage_per_customer">Max Usage Per Customer (Blank For Unlimited) <span class="required"></span></label>
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <input type="text" class="form-control" value="<?php echo set_value('max_usage_per_customer', isset($data->max_usage_per_customer)?$data->max_usage_per_customer:""); ?>" name="max_usage_per_customer">
                </div>
            </div>

        </div>
        <div class="form-group">
            <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-4 submit-cls">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href='<?php echo base_url('admin/manage_promo_code'); ?>' class="btn btn-primary">Cancel</a>
            </div>
        </div>

    </form>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#valid_from,#valid_to').datetimepicker({
            useCurrent: false,
            <?php if(!isset($data)){ ?>
            minDate: moment(),
            <?php } ?>
            format: 'DD-MM-YYYY'
        });

        $('#valid_from').datetimepicker().on('dp.change', function (e) {
            var incrementDay = moment(new Date(e.date));
            $('#valid_to').data('DateTimePicker').minDate(incrementDay);
            $(this).data("DateTimePicker").hide();
        });

        $('#valid_to').datetimepicker().on('dp.change', function (e) {
            var decrementDay = moment(new Date(e.date));
            $('#valid_from').data('DateTimePicker').maxDate(decrementDay);
             $(this).data("DateTimePicker").hide();
        });
    });
</script>  