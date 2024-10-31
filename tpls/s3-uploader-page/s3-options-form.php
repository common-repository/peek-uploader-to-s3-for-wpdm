    <div class="tab-content" style="padding: 10px; max-width: 600px">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-cog color-purple"></i> &nbsp; Choose options:</div>
            <div class="panel-body" style="padding-left:0;">
                <div class="s3u-label">
                    <label class="radio-inline">
                        <input type="checkbox" name="options" id="ta" value="acceleration" <?php do_action('s3u_s3_option', 'acceleration'); ?>> Transfer Acceleration
                    </label>
                    <label class="radio-inline">
                        <input type="checkbox" name="options" id="cf" value="cloudfront" <?php do_action('s3u_s3_option', 'cloudfront'); ?>> CloudFront
                    </label>
                </div>
            </div>


            <div id="footer_options" class="panel-footer" style="font-size: 8pt; padding: 5px 10px; border-top: none">
                <div style="float: left">
                    <img align="right" id="loading_options" style="position: absolute;display:none;margin-left:5px; margin-top:4px" src="<?php echo admin_url('images/spinner.gif'); ?>" />
                </div>
                <div id="notice_options" style="padding: 5px 10px">
                    <i class="fa fa-info-circle"></i> &nbsp; Please, select <kbd title="Amazon S3 Transfer Acceleration enables fast, easy, and secure transfers of files over long distances between your client and an S3 bucket" class="bg-white text-info">Transfer Acceleration</kbd> or enable <kbd title="For using buckets with Cloudfront service, you should have CloudFront distribution" class="bg-white text-info">CloudFront</kbd>
                </div>
            </div>
        </div>
    </div>


<script type="text/javascript">

    ta_selected();
    jQuery('#ta').on('change', function () {
        ta_selected();
    });

    function ta_selected(hide) {
        var ta=jQuery('#ta');
        var target=jQuery('[name="s3u_acceleration_button"]');
        if(ta.prop('checked')) target.fadeIn(250);
        else target.fadeOut(250);
    }

    cf_selected(true);
    jQuery('#cf').on('change', function () {
        cf_selected();
    });

    function cf_selected(hide) {
        var cf=jQuery('#cf');
        var target=jQuery('#cf-tab');
        var target2=jQuery('.distribution-buttons');
        if(cf.prop('checked')) {
            target.fadeIn(250);
            target2.fadeIn(250);
        }
        else {
            if(hide){
                target.hide();
                target2.hide();
            }
            else{
                target.fadeOut(250);
                target2.fadeOut(250);
            }
        }
    }


//    jQuery('#s3u_options_button').on('click', function () {
    jQuery('[name="options"]').on('change', function () {
        var options=[];
        jQuery('#loading_options').fadeIn();
        jQuery('input[name="options"]').each(function(){
            options.push({'name': jQuery(this).val(), 'value': jQuery(this).prop('checked')});
        });

        jQuery.post(ajaxurl+'?s3u=1&action=S3U_Update',{fields:options, model:'Option'},function(res){
            var footer=jQuery('#footer_options');
            var notice=jQuery('#notice_options');
            var errors=jQuery.parseJSON(res);
            if(errors.length>0){
                footer.attr('class', 'panel-footer alert-danger');
                notice.html('');
                jQuery.each(errors, function(index, val){
                    notice.append('<i class="fa fa-thumbs-down"></i> &nbsp; '+val+'<br />');
                });
            }else{
                footer.attr('class', 'panel-footer alert-success');
                notice.html('<i class="fa fa-thumbs-up"></i> &nbsp; Successful update');
            }
            jQuery('#loading_options').fadeOut();
        });
    });

</script>
