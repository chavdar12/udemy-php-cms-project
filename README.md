# Udemy PHP for Beginners – CMS Project

My solutions for the Udemy course "PHP for Beginners – Become a PHP Master – CMS Project": the beginner PHP topics as
small tested exercises, then the course's blog CMS rebuilt in modern PHP 8.5 without a framework. Every lesson and the
CMS have plain PHP test scripts, so nothing needs Composer.

| #   | Part                 | Project                                                               | Covers                                                                                   |
| --- | -------------------- | --------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| 001 | PHP basics           | [Variables and Arrays](001-PHP-Basics/001-Variables-And-Arrays)       | variables, string interpolation, array functions                                         |
|     |                      | [Control Flow](001-PHP-Basics/002-Control-Flow)                       | `if` / `elseif`, `match`, `for`, `foreach`, `while`                                      |
|     |                      | [Functions](001-PHP-Basics/003-Functions)                             | default and variadic parameters, recursion, closures, arrow functions                    |
|     |                      | [Forms](001-PHP-Basics/004-Forms)                                     | validating untrusted input, escaping output, re-filling a form                          |
|     |                      | [Cookies and Sessions](001-PHP-Basics/005-Cookies-And-Sessions)       | a session visit counter, a validated theme cookie, logout                               |
|     |                      | [OOP Basics](001-PHP-Basics/006-OOP-Basics)                           | classes, constructor promotion, interfaces, abstract classes, statics, readonly         |
|     |                      | [Database Basics](001-PHP-Basics/007-Database-Basics)                 | PDO CRUD with prepared statements, `password_hash` / `password_verify`                  |
| 002 | CMS project          | [CMS](002-CMS)                                                        | posts, categories, search, authors, moderated comments, accounts, admin panel and roles |

## Run the tests

```bash
cd 002-CMS/tests
php test_front.php        # every test_*.php prints "N passed, 0 failed" and exits 1 on a failure
php test_admin.php
php test_security.php
```

## The CMS

A front controller (`public/index.php`) hands the request to `Cms\App`, which holds the route table and returns a
response; templates in `templates/` escape every value with `e()`.

- **Visitors**: published posts five per page, a post page with a view counter, categories, author pages, search by
  title or tag, comments that wait for approval, register, log in, and a contact form.
- **Admins** (`/admin`): dashboard counts, posts (draft / published) with create, edit and delete, categories (add,
  rename, delete when empty), comment approval, users and roles, contact messages, and a profile page that every
  logged-in user can open.
- **Security**: PDO prepared statements only, `password_hash`, a CSRF token on every form (rotated at login),
  `session_regenerate_id` on login and logout, HttpOnly + SameSite session cookie, role checks on every admin route,
  and a Content-Security-Policy header.

Run it with SQLite:

```bash
cd 002-CMS
ADMIN_PASSWORD=choose-a-password php bin/setup.php   # creates var/cms.sqlite and the user "admin"
php -S localhost:8000 -t public public/index.php     # http://localhost:8000
```

Or with MySQL 8.4 in Docker (`schema.sql` is MySQL; `Db::migrate()` adapts it for SQLite):

```bash
cd 002-CMS
ADMIN_PASSWORD=choose-a-password docker compose up --build   # http://localhost:8000
```

Post images from the course are left out; posts are text only.

## Certificate

[Udemy · PHP for Beginners – Become a PHP Master – CMS Project](Resources/Certificate.pdf)

## License

[MIT](LICENSE)
