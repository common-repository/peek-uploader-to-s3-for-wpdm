    <div class="panel panel-default" id="wpdm-wrapper-panel">
        <div class="panel-heading">
            <b><i class="fa fa-amazon color-purple"></i> &nbsp; <?php echo __('S3-Uploader', 's3-uploader'); ?></b>
        </div>
    </div>

    <div class="tab-content" style="padding: 10px; max-width: 800px">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-cloud-upload color-purple"></i> &nbsp; Choose bucket for upload</div>
            <div class="panel-body">
                <div class="list-group">
                    <?php
                    try {
                        $buckets = $this->s3->listBuckets()["Buckets"];
                        foreach ($buckets as $bucket) {
                            ?>
                            <label class="list-group-item">
                                <div style="margin: 0 0 30px;">
                                    <div class="radio-inline pull-left" style="font-weight: 700">
                                        <input type="radio" name="s3u_bucket" value="<?php echo $bucket['Name']; ?>">
                                        <?php echo $bucket['Name']; ?>
                                    </div>
                                    <div class="radio-inline pull-right">
                                        <span class="distribution-buttons" style="padding-right: 5px">
                                            <?php do_action('s3u_s3_distribution', array($bucket['Name'], true)); ?>
                                        </span>
                                        <span>
                                            <?php do_action('s3u_s3_transferacceleration', $bucket['Name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </label>
                            <?php
                        }
                    } catch (S3Exception $e) {
                    }
                    ?>
<!--                    <label class="list-group-item">-->
<!--                        <div style="margin: 0 0 25px;">-->
<!--                            <label class="radio-inline pull-right">-->
<!--                                <kbd style="font-weight: 700; margin-right: 10px;" class="bg-white text-info">If you made changes through Amazon, then click: </kbd>-->
<!--                                <button id="" name="" style="" class="btn btn-success btn-xs" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Refresh changes</button>-->
<!--                            </label>-->
<!--                        </div>-->
<!--                    </label>-->
                </div>

                <div class="btn-group">
                    <div style="float: left;">
                        <div class="pull-left">
                            <button id="qweaws-plupload-browse-button" type="button" onclick="uploadAllFiles(null)" class="btn btn-primary"><i class="fa fa-cloud-upload"></i>&nbsp; Upload</button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon" style="padding-left: 30px;">Or create new Bucket:</span>
                            <input id="new_bucket" type="text" class="form-control" placeholder="Name" />
                            <span class="input-group-btn">
                                <button type="button" onclick="createBucketAndUpload()" class="btn btn-primary"><i class="fa fa-plus-circle"></i>&nbsp; Create and Upload</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="footer" class="panel-footer" style="font-size: 8pt; padding: 5px 10px; border-top: none">
                <div style="float: left">
                    <img align="right" id="awloading" style="position: absolute;display:none;margin-left:5px; margin-top:4px" src="<?php echo admin_url('images/spinner.gif'); ?>" />
                </div>

                <div id="aws-filelist" style="padding: 5px 10px"><i class="fa fa-info-circle"></i> &nbsp; Upload all files from the folder <kbd title=".../wp-content/uploads/download-manager-files/*" class="bg-white text-info">download-manager-files</kbd> to the selected Bucket</div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    jQuery('[name="s3u_acceleration_button"').on('click', function () {
        var werewolf=jQuery(this);
        var bucket=werewolf.attr('id');
        jQuery('#awloading').fadeIn();
        werewolf.find('i').fadeIn();
        jQuery.post(ajaxurl+'?s3u=1&action=S3U_TransferAcceleration_Toggle',{bucket:bucket},function(res) {
            var footer=jQuery('#footer');
            var notice=jQuery('#aws-filelist');
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

            var tpl='<i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>';
            if(werewolf.hasClass('btn-success'))
                werewolf.removeClass('btn-success').addClass('btn-danger').html(tpl+'Decelerate');
            else
                werewolf.removeClass('btn-danger').addClass('btn-success').html(tpl+'Accelerate');

            jQuery('#awloading').fadeOut();
            werewolf.find('i').fadeOut();
        });
    });

    jQuery('[name="s3u_distribution_create_button"').on('click', function () {
        var werewolf=jQuery(this);
        var bucket=werewolf.attr('id');
        jQuery('#awloading').fadeIn();
        werewolf.find('i').fadeIn();
        jQuery.post(ajaxurl+'?s3u=1&action=S3U_DistributionCreate',{bucket:bucket},function(res) {
            var footer=jQuery('#footer');
            var notice=jQuery('#aws-filelist');
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

                var kbd = werewolf.parent().find('.distribution_link');
                kbd.find('span').html('InProgress');
                kbd.removeClass('bg-info').removeClass('text-info').addClass('bg-warning').addClass('text-warning')
            }

            var tpl='<i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>';

            werewolf.removeClass('btn-success').addClass('btn-warning').addClass('disabled').html(tpl+'Created');

            jQuery('#awloading').fadeOut();
            werewolf.find('i').fadeOut();
        });
    });

    jQuery('[name="s3u_distribution_button"').on('click', function () {
        var werewolf=jQuery(this);
        var bucket=werewolf.attr('id');
        jQuery('#awloading').fadeIn();
        werewolf.find('i').fadeIn();
        jQuery.post(ajaxurl+'?s3u=1&action=S3U_Distribution_Toggle',{bucket:bucket},function(res) {
            var footer=jQuery('#footer');
            var notice=jQuery('#aws-filelist');
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

                var kbd = werewolf.parent().find('.distribution_link');
                kbd.find('span').html('InProgress');
                kbd.removeClass('bg-info').removeClass('text-info').addClass('bg-warning').addClass('text-warning')
            }

            var tpl='<i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>';
            if(werewolf.hasClass('btn-success'))
                werewolf.removeClass('btn-success').addClass('btn-danger').html(tpl+'Not distribute');
            else
                werewolf.removeClass('btn-danger').addClass('btn-success').html(tpl+'Distribute');

            jQuery('#awloading').fadeOut();
            werewolf.find('i').fadeOut();
        });
    });

    function createBucketAndUpload(){
        var new_bucket=jQuery('#new_bucket').val();
        if(new_bucket==null||new_bucket==''||new_bucket=='undefined') return false;

        jQuery('#awloading').fadeIn();
        jQuery.post(ajaxurl+'?s3u=1&action=CreateBucket',{bucketname:new_bucket},function(res){
            if(res=='ok') uploadAllFiles(new_bucket);
            else{
                jQuery('#aws-filelist').html('<i class="fa fa-info-circle"></i> &nbsp; '+res);
                jQuery('#footer').attr('class', 'panel-footer alert-danger');
                jQuery('#awloading').fadeOut();
            }
        });
    }
    function uploadAllFiles(new_bucket){
        var bname='';
        if(new_bucket!='' && new_bucket!=null && new_bucket!='undefined') bname=new_bucket;
        else{
            jQuery('#awloading').fadeIn();
            bname=jQuery('input[name=s3u_bucket]:checked').val();
        }

        if(bname==null||bname==''||bname=='undefined') return false;


        jQuery.post(ajaxurl+'?s3u=1&action=s3UploadAllFiles',{bucketname:bname},function(res){
            jQuery('#awloading').fadeOut();
            if(res!='ok'){
                jQuery('#aws-filelist').html('<i class="fa fa-file-o"></i> &nbsp; '+res+'<i class="fa fa-info-circle"></i> &nbsp; Other files have been successfully uploaded!');
                jQuery('#footer').attr('class', 'panel-footer alert-danger');
            }else{
                jQuery('#aws-filelist').html('<i class="fa fa-cloud-upload"></i> &nbsp; Successfully uploaded!');
                jQuery('#footer').attr('class', 'panel-footer alert-success');
            }
        });
    }
</script>