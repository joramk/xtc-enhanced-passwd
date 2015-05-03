# Enhanced password encryption for xt:commerce, modified eCommerce, commerce:SEO and Gambio GX2 #
This project aims at enhancing the old xt:commerce 3.04 and similar shop system with todays password encryption algorithms. It provides different algorithms to use and re-hashes user passwords without user-interaction on login to make them more secure.

**Project status:** `Did some tests. Works so far. Use at your own risk. Do your own tests.`

Currently tested only with a vanilla installation of xt:commerce 3.04 SP 2.1 and PHP 5.3 on CentOS 6.5.

## Downloads for all compatible shop systems ##
| **Shop system** | **Sourcecode branch** | **Get the module** (the stable version is recommended if available) |
|:----------------|:----------------------|:--------------------------------------------------------------------|
| **xt:commerce 3.04 SP2.1** | [xtc304sp21](http://code.google.com/p/xtc-enhanced-passwd/source/browse/?name=xtc304sp21) | [Latest development branch](http://xtc-enhanced-passwd.googlecode.com/archive/xtc304sp21.zip) / Current stable version (not released yet) |
| **modified eCommerce 1.06** | [modified106](http://code.google.com/p/xtc-enhanced-passwd/source/browse/?name=modified106) | [Latest development branch](http://xtc-enhanced-passwd.googlecode.com/archive/modified106.zip) / Current stable version (not released yet) |
| **Gambio GX2 2.0.14.1** | [gambio-shop](http://code.google.com/p/xtc-enhanced-passwd/source/browse/?name=gambio-shop) | [Latest development branch](http://xtc-enhanced-passwd.googlecode.com/archive/gambio-shop.zip) / Current stable version (not released yet) |
| **commerce:SEO 2.4.5**| [commerceseo](http://code.google.com/p/xtc-enhanced-passwd/source/browse/?name=commerceseo) | [Latest development branch](http://xtc-enhanced-passwd.googlecode.com/archive/commerceseo.zip) / Current stable version (not released yet) |

## Dependencies ##
| **Algorithm used** | **Minimum version required** | **Hashed by default with** | **Usage recommendation** |
|:-------------------|:-----------------------------|:---------------------------|:-------------------------|
| `ALGORITHM_MD5` | PHP 4+ | MD5 | **Do not use** (exists for backward compatibility only) |
| `ALGORITHM_SHA1SALT` | PHP 4+ | SHA1 + salt | **Do not use** (exists for backward compatibility only) |
| `ALGORITHM_BCRYPT` | PHP 4+ | Blowfish, fallback to EDES | Compatible (use only if mcrypt or PHP5 isn't available) |
| `ALGORITHM_PBKDF2` |PHP 5+ | SHA256 | Customizable (hashing method can be changed) |
| `ALGORITHM_SCRYPT` | PHP 5+, php-scrypt 1.2+ | Salsa20 | **Best security** (see http://en.wikipedia.org/wiki/Scrypt) |

## Installation instructions ##
The password enhancement update can be applied to exisiting shops equally to new shops. For new shops, complete the initial installation of xt:commerce first, then apply the database patch and copy over the existing files the changed ones. If you already altered any of these files in question by yourself you have to incorporate the changes from this module into your existing files. **Make sure you have a backup before proceeding.**

### 1. Extract and configure ###
Extract all files from the downloaded archive to a temporary directory. Find the file `inc/xtc_encryption_wrapper.inc.php`.
You can change between PBKDF2 and other encryption algorithms.
Change the line containing `$ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2` to one of these:
```
public static $ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2;
public static $ALGORITHM_DEFAULT = self::ALGORITHM_SCRYPT;
public static $ALGORITHM_DEFAULT = self::ALGORITHM_BCRYPT;
public static $ALGORITHM_DEFAULT = self::ALGORITHM_MD5;
public static $ALGORITHM_DEFAULT = self::ALGORITHM_SHA1SALT;
```
You can switch back to the xt:commerce integrated MD5 algorithm if you want, but this is of course not recommended.
You can change the algorithm, number of iterations, salt and password hash length in PBKDF2 encryption by altering these values in `includes/classes/pbkdf2/xtc_pbkdf2.php`:
```
public static $PBKDF2_HASH_ALGORITHM = "sha256";
public static $PBKDF2_ITERATIONS     = 262144;
public static $PBKDF2_SALT_BYTES     = 24;
public static $PBKDF2_HASH_BYTES     = 24;
```
Available options to change the difficulty of the scrypt implementation are CPU difficulty, memory difficuly and parallel difficulty. They are configurable in the file `includes/classes/scrypt/xtc_scrypt.php`:
```
public static $CPU_DIFFICULTY      = 16384;
public static $MEMORY_DIFFICULTY   = 8;
public static $PARALLEL_DIFFICULTY = 1;
public static $SALT_LENGTH         = 8;
```
The available options for bcrypt algorithm iteration count and weak fallback are located in the file `includes/classes/bcrypt/xtc_bcrypt.php`:
```
public static $ALGORITHM_ITERATION_COUNT     = 8;     // valid values are 4 to 31
public static $ALGORITHM_ALLOW_WEAK_FALLBACK = false;
```
By default passwords get re-hashed with the current default algorithm on user logins. You can switch this behaviour off if you have good reasons to do so in `inc/xtc_encryption_wrapper.inc.php` by changing the `$UPDATE_PASSWORDS` parameter:
```
public static $UPDATE_PASSWORDS = false;
```
To use the scrypt algorithm, you have to install the PECL module scrypt. You can simply do this by entering this console command as root user:
```
pecl install scrypt
```
Remember to include the `scrypt.so` extension to your `php.ini` file by adding this line and to restart your webserver:
```
extension=scrypt.so
```

### 2. SQL Database update ###
Then you have to alter your existing database. Login to your MySQL server by console or phpMyAdmin, switch to your xt:commerce database and enter this SQL command:
```
ALTER TABLE `customers` CHANGE `customers_password` `customers_password` VARCHAR( 255 ) NOT NULL;
```

### 3. Copy over the new files ###
At last you need to copy all files from the temporary directory to your online shop directory on your server replacing the exisiting ones. If yo have already altered any of these files you should not simply overwrite them. Instead incorporate all changes from this module in your existing files. Usually the files in the `inc` directory can safely be overwritten. The login.php file should be handled with care if you did some changes in there. The `.sql` file is not intended to get copied to your web server. Remember: _Always make a backup before replacing or altering any files!_

Here is a diff for the linux `patch` command for the `login.php` file. As you can see there are five lines of code which need to get added directly after the password has been successfully validated. This is only needed if you want to update existing password hashes in your database. This is highly recommended but only optionally.
```
--- a/login.php      2006-07-24 20:17:12.000000000 +0200
+++ b/login.php      2014-02-22 21:06:16.280231512 +0100
@@ -56,6 +56,11 @@
                        $_GET['login'] = 'fail';
                        $info_message = TEXT_LOGIN_ERROR;
                } else {
+                       if (class_exists('xtc_encryption_wrapper') &&
+                                       xtc_encryption_wrapper::needsAlgorithmUpdate($check_customer['customers_password'])) {
+                               xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_password = '".
+                                               xtc_encryption_wrapper::createHash($password) ."' WHERE customers_id='". $check_customer['customers_id'] ."'");
+                       }
                        if (SESSION_RECREATE == 'True') {
                                xtc_session_recreate();
                        }
```

## Password hash examples ##
| **Hash algorithm** | **Example password hash** (in few words you can say a longer string means better encryption) |
|:-------------------|:---------------------------------------------------------------------------------------------|
| **MD5** | `97f79cd042978406ab3dc3234c1e89b7` |
| **SHA1+SALT** | `28ac406465483a34044ead48274e1853a237cda8f` |
| **BCRYPT** | `$2a$12$ZfvbOdYXSfpW5gQReEsUVu5uzcHcQtyzQQjy2RDiaLzo328HETNmD` |
| **PBKDF2** | `sha256:262144:PXAUqMgdFoYOiI3CfLU1kbDFELEMgXnIW:jXDdTSYd++4tEPbj4S8R9jf4AmPLHDa9` |
| **SCRYPT** | `65536$12$4$cXpGFUV3M4J6Lju2UFVKdg==$28312ea3370cdd367ffab983bfc9b7e28887d430f229d2c6aa51c180f55a0eaa` |

## External resources ##
  1. http://www.php.net/manual/en/book.mcrypt.php
  1. http://pecl.php.net/package/scrypt
  1. http://www.php.net/crypt
  1. http://github.com/DomBlack/php-scrypt
  1. http://github.com/defuse/password-hashing
  1. http://defuse.ca/php-pbkdf2.htm
  1. http://crackstation.net/hashing-security.htm
  1. http://www.xtc-load.de/2008/07/xtcommerce-v304-sp21/
  1. http://www.xt-commerce.com
  1. http://codahale.com/how-to-safely-store-a-password/
  1. http://www.openwall.com/phpass/
  1. http://commons.wikimedia.org/wiki/User:RRZEicons