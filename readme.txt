=== Peak Uploader to S3 for WPDM ===
Contributors: codelobster, arsencher
Tags: Amazon S3, upload, Cloudfront, Download Manager, bucket, distribution
Requires at least: 4.2
Tested up to: 4.8.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin extends Wordpress Download Manager plugin with Amazon S3 and Cloudfront integration

== Description ==

S3 Uploader extends Wordpress Download Manager plugin, helps you upload files to Amazon S3, create buckets and Cloudfront distributions.

### Features

* Create Amazon S3 buckets
* Upload all Downloads files to particular bucket
* Create Cloudfront distributions
* Accelerate S3 buckets
* Choose default bucket or distribution for new Downloads files
* Add files from S3 using widget on Downloads file edit page

Select the checkbox option CloudFront or Transfer Acceleration to enable them for the plugin.
After that, buttons will be available that will allow you to work remotely with Amazon Cloudfront and S3 services:

Accelerate/Decelerate buttons - enable or disable the Transfer Acceleration for the bucket.
Create distribution - when you click, a distribution with default settings will be created for the bucket (also, you can edit or create distributions through the Amazon website with the settings you need).
Distribute/Not distribute - enable or disable distribution for the bucket

If both buttons (Distribute and Accelerate) were enabled for the bucket, then the link to the bucket will be generated using the CloudFront service, not Transfer Acceleration.
In this case, if you want to use Transfer Acceleration, click Not distribute for this bucket or turn off CloudFront option.

== Installation ==

1. Go to Plugins > Add New.
1. Search for "S3 Uploader".
1. Click "Install Now".

OR

1. Download the zip file.
1. Upload the zip file via Plugins > Add New > Upload.
1. Install and activate [Wordpress Download Manager](https://wordpress.org/plugins/download-manager/) plugin

Activate the plugin. Look for "Downloads > Settings > Cloud Storage tab" in the admin menu.
Set Amazon Access and Secret keys
Go to plugin page "Downloads > S3-Uploader"

== Screenshots ==
1. Peak Uploader Widget
2. Peak Uploader settings page

== Changelog ==
1.0
Initial commit.
