# Wordpress Database Fixer
WP Database SQL index, Primary Keys Fixer


The script will give you DELETE MODIFY/ALTER table queries & remove those corrupted rows in wp db. It will also enable auto increments.
Before running this script, please make a backup first.

This will only fix your Core tables (users, posts etc..) It will not fix any other non WP Core tables.


#### This script will fix the following database symptoms and errors:

1. PHP errors in your log: “WordPress database error: [Duplicate entry ‘0’ for key ‘PRIMARY’]”
2. "Incorrect table definition; there can be only one auto column and it must be defined as a key”
3. Not able to create new pages, all pages have the Publish button replaced with Submit for review. 
No permalink, just “?preview=true”


# ![Markdown Here logo](https://raw.githubusercontent.com/trgcyln/wordpress-database-fixer/master/Symptom-2.png)
# ![Markdown Here logo](https://raw.githubusercontent.com/trgcyln/wordpress-database-fixer/master/Symptom.png)
