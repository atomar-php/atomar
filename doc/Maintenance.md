Maintenance Mode
----

Maintenance mode allows you to disable the site for all but administrators and the super user.
You can enable/disable maintenance mode by clicking "Enable Maintenance Mode" from the [/admin](/admin) page.

###Configuration
Extensions may override the default maintenance page by setting a new handler with `S::set_maintenance_mode_handler(Closure $function)`.

Maintenace mode is initiated right after the preprocess_boot hook so you must override the maintenance handler within that hook for it to take affect.