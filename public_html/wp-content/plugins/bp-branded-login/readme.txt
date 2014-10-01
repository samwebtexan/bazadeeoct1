Change log
1.1.3
Fixes for Bp 1.6
1.1.2.1
Fix the adminbar showing double login menu, hook to bp_init for removing bp core login links

1.1.1
Fix the wrong redirect on comment page and wp-admin, fixed a line in blogin/login.php
1.1
Catch the form url if they are generated using site_url("wp-login.php"); and regenerate them to site_url(BP_LOGIN_SLUG);
