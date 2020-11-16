# pagetoc
This is a WordPress plugin that generates a hierarchical table of contents for a page.

## Setup

This doesn't have an admin panel; pop it in and activate it, and it'll be ready to go. To add a table of contents to a page, add a custom field named `has-index` and set that to `true`.

## Important!

* **Make sure** that the header hierarchy is correct; that is, that `h2` elements have `h3` children, and `h3`s have `h4` children, etc. Going from `h2` to `h4` will generate an error. This is something I need to fix.
