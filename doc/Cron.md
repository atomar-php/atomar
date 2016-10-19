Cron
----

Atomar is built with a cron system that can be hooked (see [Hooks]) into using the hook `hook_cron()`. When setting up your server you should configure a cron job to execute `http://example.com/!/cron/?token=your_cron_token`.

The cron token can be set in your configuration file and in case you were wondering is accessible as `S::$config['cron_token']`. Your cron token should be unique and private to avoid others misusing cron to perform DoS attacks on your site.

###Quick Start
In order to run cron manually you can click the "Run Cron" button from the [/admin](/admin) page.

###Usage
When you create an extension using the extension wizard (see [Extensions]) a hook will automatically be generated for you in your extension file. You can execute any code within this hook and anything you print/echo will appear in the cron log.

    // assuming an extension named 'my_extension'
    function my_extension_cron() {
      echo 'Hello world';
    }

>NOTE: At this point there is no built in scheduling for cron jobs,
>but it is on the todo list. This means that everything hooking into the cron system will run each time cron runs.
> If you do not want this to happen you must handle this yourself.

###TODO:
* Create a cron scheduling system so that extensions can independently schedule their cron jobs.

[Extensions]:/atomar/documentation/core/Extensions
[Hooks]:/atomar/documentation/core/Hooks