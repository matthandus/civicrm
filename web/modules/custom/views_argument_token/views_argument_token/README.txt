Views Token Argument allows you to use token as contextual
filter for Views.

You can use both current user values or the entity related
to the current page (for example the current node on node/x
pages, or the user on user/x, etc.).

Moreover you have an option to take the raw value for the fields
(for example ID instead of linked title provided by token module).

Example use case : a user has an entity reference to taxonomies
and you wish to show in a view all content related to the terms
the user has selected. Or you wish to show last contents that
have the same category of the current viewed node...

There is also a debug mode to show you the final value after processing.

If you want to get all values if the value is empty, check the box
and enable in the fieldgroup "exception" the "all" argument.

This module has no dependencies but uses Token module to browse 
available tokens.