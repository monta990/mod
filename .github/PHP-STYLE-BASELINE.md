# PHP style baseline

Measured on 2026-11-02 with GLPI 12.0 / PHP 8.5 and PHP-CS-Fixer 3.95.27
using the GLPI @PER-CS3x0 rules.

Four of twelve first-party PHP files have fixable formatting changes. CI
fails if more than 4 files need formatting. Changed runtime PHP files are
checked separately and must have zero fixable changes.

This is historical formatting debt, not a waiver for new or changed code.
Reduce the baseline in focused, reviewable changes until PHP-CS-Fixer is clean.
