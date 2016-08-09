### Menus

The system uses a simple array based menu system that may be modified before rendering in the templates. Two menus are predefined by the system, although these may be overridden and/or new menus added:

*   `primary_menu` generally displayed along the top of the website.
*   `secondary_menu` generally displayed on the side of the website and is used by default to display the administrative menu.
    Menus may be accessed and modified using the Site class `S`. For example `S::$menu['primary_menu']` will fetch the primary_menu.

The menu rendering is pretty complex and includes support for authentication, ordering, and menues. 
>TODO: Update the menu documentation.

