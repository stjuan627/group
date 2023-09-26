# Group

The Group module allows you to create arbitrary collections of your content and
users on your website, and grant access control permissions on those
collections.

This module is designed to be an alternative to Organic Groups (OG). Rather than
using Entity References to track the relationships between groups users, terms,
and other entity types, the Group module creates groups as entities, making them
fully fieldable, extensible, and exportable.


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see 
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Note: By default, the admin user (user id #1) does not have any special
permissions, as opposed to many other Drupal modules. So the admin user needs
to be granted the group admin role. For more, see 
[Full guide to Group version 2 and 3 - #03 Roles and permissions](https://www.youtube.com/watch?v=xo2z8NuKEH4).

### Creating a new group type

Add a Group Type at 'Administration > Groups' (`admin/group/types`) for
example: 'School'.


### Configuring the new Group Type (optional)

- Add fields to the 'School' Group Type from 'Manage Fields'
  (`/admin/group/types/manage/school/fields`).
- Manage form display from 'Manage Form Display'
  (`/admin/group/types/manage/school/form-display`).
- Manage display from 'Manage Display'
  (`/admin/group/types/manage/school/display`).
- Add roles for this group from 'Edit Group Roles'
  (`/admin/group/types/manage/school/roles`).
- Configure permission for each role from 'Edit Permissions'
  (`/admin/group/types/manage/school/permissions`).


### Creating a new group

Create a group by clicking the 'Add group' link from 'Administration >
Groups' (`/admin/group`) and select 'School'. Fill in and submit the form to
complete the group creation.


### Configuring the new Group (optional)

Add members to this group via the 'Members' tab on the group's main page, or
the 'Members' link from 'Administration > Groups > [Group Name]'.


### Adding content to a group:

- For each Group Type, you first need to specify which content types can be
  used. For instance, to enable 'Article' as a Group Content Type for the
  'School' Group Type, go to the group listing at 'Administration > Groups >
  Group Types > Type > Set Available Content'
  (`admin/group/types/manage/school/content`) and click 'Install' next to
  'Group node (Article)'.
- Go to your Group's main page and click either 'Nodes' (`group/1/nodes`)
  or 'All Entities' (`group/1/content`). Click either 'Create node' to create
  a node directly in this group, or click 'Relate node' to make an existing
  node part of this group.


## Maintainers

- Kristiaan Van den Eynde (kristiaanvandeneynde) - https://www.drupal.org/u/kristiaanvandeneynde
- Derek Wright (dww) - https://www.drupal.org/u/dww

This project has been sponsored by Deeson.
