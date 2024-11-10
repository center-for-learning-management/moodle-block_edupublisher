# moodle-block_edupublisher

This plugin allows to build an internal course database that is taggable and searchable via meta information. Any imports are moved to the desired position in a course without being mixed with existing resources.

## Installation

It is recommended that all metadata fields describing the learning packages are indexed in the database. Unfortunately, Moodle does not allow fields of type XMLDB_TYPE_TEXT to be indexed. Therefore these fields are not flagged to be indexed
upon installation or update from a previous version of this plugin. Consequently, after the installation probably the following fields should be indexed in the database itself to enhance performance of the search function:

- block_edupublisher_md_def
  - summary
  - tags
- block_edupublisher_md_edu
  - curriculum
  - educationallevel
  - schooltype
  - type
- block_edupublisher_md_eta
  - kompetenzen
  - stundenablauf
  - voraussetzungen
  - vorkenntnisse

## Configuration

### eduPublisher category

After installation you should specify a course category where shared resources are placed. For every shared resource eduPublisher will create a course.

You must grant "Authenticated users" the capability "moodle/backup:backuptargetimport" in that course category, so that they will be able to import from these courses.

Please ensure that "Authenticated users" (and if you like also "Guest users") have the capability course:view, so that they can see all courses in that category. Packages that are not enabled will be hidden anyway.

### Maintainers

You can specify maintainers for the types "default", "etapas" and "eduthek" using the capabilities. It is advisable to create such roles that are assigned in the coursecategory-context of the category where all eduPublisher-Packages are
published.
