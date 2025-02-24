=== LearnPress - Certificates ===
Contributors: thimpress, tunnhn, leehld, phonglq, tungnx
Donate link:
Tags: certificate, lms, elearning, e-learning, learning management system, education, course, courses, quiz, quizzes, questions, training, guru, sell courses
Requires at least: 6.3
Tested up to: 6.7
Stable tag: 4.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

*** Library ***
download.min.js v4.2 - http://danml.com/download.html
fabric.min.js v1.4.13 - http://fabricjs.com/
md5 v1.4.13 - http://fabricjs.com/

Create certificates for courses of LearnPress.

== Description ==
Create certificates for courses of LearnPress.

== Installation ==

**From your WordPress dashboard**
1. Visit 'Plugin > Add new'.
2. Search for 'LearnPress Certificates'.
3. Activate LearnPress from your Plugins page.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 4.1.4 (2025-02-06) =
~ Fixed: minor bug.

= 4.1.3 (2025-02-05) =
~ Display button certificate on single course modern layout.

= 4.1.2 (2024-06-15) =
~ Tweak: feature assign certificate.
~ Tweak: get_link_cert_bg_by_course, use site_url for get link file upload.
~ Fixed: missing library wp-color-picker.
~ Fixed: size images svg.

= 4.1.1 (2024-06-03) =
~ Fixed: error can't assign Certificate.

= 4.1.0 (2024-05-14) =
~ Fixed: link background certificate.

= 4.0.9 (2024-04-11) =
~ Added: load more list Certificates on Edit Course.
~ Fixed: barcode not show on Certificate.

= 4.0.8 (2024-01-09) =
~ Fixed: error wrong link certificate on Profile Page when Admin view User's Profile.
~ Added: defer js.
~ Fixed: minor bug.

= 4.0.7 (2023-08-10) =
~ Fixed: no load direct font Google via Url of Google.
~ Fixed: format date on certificate.

= 4.0.6 (2023-04-11) =
~ Fixed: error wp_media not doing when editing the certificate.
~ Fixed: error Admin can't view certificate of another User on Profile Page.

= 4.0.5 (2023-03-25) =
~ Optimize: rewrite rules with LP v4.2.2.3.
~ Added: load list certificate with ajax via API(type load more).
~ Fixed: case with new user(has just create account), and get certificate will be 404.
~ Fixed: style certificates on Profile Page.

= 4.0.4 (2023-01-17) =
~ Load no limit certificate.
~ Modified: call template.
~ Modified: styles.

= 4.0.3 =
~ Fixed: error show certificates on the "Profile" page.

= 4.0.2 =
~ Fixed: text set on certificate very long.
~ Fixed: error with LP 4.1.5 reset process course when bought Certificate.
~ Modified: rewrite buy certificate.
~ Fixed: download PDF certificate with small file size.

= 4.0.1 =
~ Fixed: Certificate title when permalink Certificate empty.

= 4.0.0 =
~ Fix compatible LP4

= 3.2.0 =
~ Fix minor bugs

= 3.1.9 =
~ Fix icon download hide if not enable social icon

= 3.1.8 =
~ Load 'vue-libs' js from LP v3.2.8
~ Fix share certificate via social

= 3.1.7 =
~ Fix redirect to page woo page checkout when buy if enable
~ Fix check can get certificate
~ Fix set price error when WC()->cart null
~ Fix download type PDF when background of cert has height long > width
~ Don't use font-awesome

= 3.1.6 =
~ Fix buy certificate via LP will reset progress = 0, reason by hook auto enroll run when completed LP order cert
~ Fix check can get certificate

= 3.1.5 =
~ Fix 404 certificate if not save setting certificate reason by add_rewrite_rule need to flush
~ Update use function wp file

= 3.1.4 =
~ Fixed canvas
~ Add download certificate type PDF
~ Fix style
~ Fix compatible with external plugins optimize js
~ Fix compatible with lazyload
~ Fix buy certificate via LP, Woocommerce ( LP - Woo 3.2.1 )
~ Optimize
~ Add option to change slug certificate
~ Fix share social
~ Add show image of Cert on Woo cart

= 3.1.3 =
~ Fixed bug path image
~ Fixed: Course end date error when finish course (LP 3.2.7.3)

= 3.1.2 =
~ Fixed bug display new image whenever clicking Download button.
~ Made Date field could be translated.
~ Updated the description data for the certificate.

= 3.1.1 =
~ Fixed bug cannot generate the end time of course.

= 3.1 =
+ Add option generate certificate to image
+ Add new layer verified link

= 3.0.5 =
~ Fixed can not remove a layer
~ Fixed font display wrong after selected
~ Fixed issue special character in certificate

= 3.0.4 =
~ Fixed content of Date field has 'X'
~ Fixed use default date format if it is not set

= 3.0.3 =
~ Fixed bug cannot view certificate at frontend
~ Fixed can not add featured image in course editor
~ Fixed can not drag drop elements in certificate editor

= 3.0.2 =
~ Fixed can not add featured image in course editor

= 3.0.1 =
~ Fixed can not add featured image in course editor

= 3.0.0 =
+ Updated to be compatible with LearnPress 3.0.0

== Other note ==
<a href="http://docs.thimpress.com/learnpress" target="_blank">Documentation</a> is available in ThimPress site.
<a href="https://github.com/LearnPress/LearnPress/" target="_blank">LearnPress github repo.</a>
