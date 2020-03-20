## 2.5.0
* Allowed Panelizer 5 to be installed alongside Lightning Layout. If you need
  to use Panelizer to add or edit default layouts, you can require Panelizer
  4 in your project via `composer require drupal/panelizer:^4.1`.

## 2.4.0
* Fixed an incompatibility between Lightning Landing Page and Lightning
  Workflow 3.14 and later.

## 2.3.0
There are no user-facing changes in this version.

## 2.2.0
* Added support for Drupal core 8.8.x.
* Updated Background Image Formatter to 1.10.
* Layout Builder Symmetric Translations will only be installed if the
  site is using the Language module. (Issue #3066811)
* Fixed a PHP warning that can occur after a cache clear. (Issue #3068755)
* Lightning Layout now allows CTools 3.0 or later.
* Lightning Layout now includes the Layout Builder Styles module as 
  a dependency.

## 2.1.0
* Added the Layout Builder Symmetric Translations module to provide basic
  translation support for landing pages.
* Updated Background Image Formatter to 1.9.
* Updated Layout Builder Library to 1.0-beta1.
* Updated Layout Builder Restrictions to 2.1.
* Updated Panels to 4.4.

## 2.0.0
* Panels and Panelizer have been replaced with Layout Builder. (Issue #2952620)

## 1.7.0
* Lightning Layout now supports Lightning Core 4.x (Drupal core 8.7.x).
* Added a description to an administrative link. (Issue #3034041)

## 1.6.0
* Updated Lightning Core to 2.12 or 3.5, which security update to Drupal core to
  8.5.9 and 8.6.6, respectively.
* Changes were made to the internal testing infrastructure, but nothing that
  will affect users of Lightning Layout.

## 1.5.0
* Many internal changes to testing infrastructure, but nothing that affects
  users of Lightning Layout.

## 1.4.0
* Fixed a bug which could cause Behat test failures due to a conflict with
  Content Moderation. (Issue #2989369)

## 1.3.0
* Allow Lightning Core 3.x and Drupal core 8.6.x.
* Updated logic to check for the null value in PanelizerWidget (Issue #2966924)
* Lightning Landing Page now checks for the presence of Lightning Workflow, not
  Content Moderation when opting into moderation. (Issue #2984739)

## 1.2.0
* Updated to Panelizer 4.1 and Panels 4.3.

## 1.1.0
* Entity Blocks was updated to its latest stable release and is no longer
  patched by Lightning Layout.
* Behat contexts bundled with Lightning Layout were moved into the
  `Acquia\LightningExtension\Context` namespace.

## 1.0.0
* No changes since last release.

## 1.0.0-rc1
* Fixed a configuration problem that caused an unneeded dependency on the
  Lightning profile. (Issue #2933445)
