<?php

class Config {
    const DB_ENGINE           = 'mysql';
    const DB_HOST             = 'localhost';
    const DB_USER             = 'rss_for_later_user';
    const DB_PWD              = 'some_long_password';
    const DB_NAME             = 'rss_for_later';

    // Create yourself a new app as a Pocket developer, and enter your app 'Consumer Key' here.
    // Ref: http://getpocket.com/developer/
    const POCKET_API_KEY      = '12437-000000000000000000000000';

    // The pipe used to inject the feed title into each item. You probably want to leave this as-is.
    const YAHOO_PIPE_INJECT_ID = 'bb7eaa73a7a36d7b2dabb534461940ee';

    // The salt used to hide the user ID in local URLs. Make this unique (in content and length).
    const SHARING_SALT        = 'kajhfg2375salj2984kj';

    // Change those to fit your installation hostname.
    const OPML_URL            = 'http://rss-for-later.pommepause.com/opml/?uuid=$uuid';
    const LOCAL_COPY_URL      = 'http://rss-for-later.pommepause.com/local/?aid=$aid&shared=$hash';
    const BASE_URL            = 'http://rss-for-later.pommepause.com';

    // Allow users to send their Twitter Home Timeline (tweets from users they follow) to Pocket.
    // Create a new app at https://dev.twitter.com/apps
    // Choose Access: Read only.
    //    and Callback URL: http://www.your_domain.com/rss-for-later/twitter_auth/
    const TWITTER_API_KEY     = '';
    const TWITTER_API_SECRET  = '';

    // (Optional) For Google Analytics
    // const GOOGLE_ANALYTICS_ID = 'UA-12345-1';
}

date_default_timezone_set('UTC');

?>
