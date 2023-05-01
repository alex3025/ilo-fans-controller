<h1 align="center">iLO Fans Controller</h1>

<p align="center">
  <img width="800" src="screenshot.png" alt="Webpage Screenshot">
  <br>
  <i>Easily manage your HP's server fans speeds, anywhere!</i>
</p>

---

> ℹ **NOTE:** The v1.0.0 is a **complete rewrite** of the tool, so any feedback is appreciated!<br>
> If you find any bug or have any suggestion, please [open an issue](https://github.com/alex3025/ilo-fans-controller/issues). Thanks! 😄

## FAQ

### Why did you make this? 🤔

See my original comment on [r/homelab](https://www.reddit.com/r/homelab/comments/rcel73/comment/hnu3iyp/?utm_source=share&utm_medium=web2x&context=3) to know the story behind this tool!

### How does it work? 🛠

This tool is a **simple PHP script** that uses the `php-curl` extension to **get the current fan speeds from the iLO REST api** and the `php-ssh2` extension to **set the fan speeds using the patched iLO SSH interface.** You can also **create custom presets** to set a specific fan configuration with one click, all with a **simple and clean web interface** made using [Alpine.js](https://alpinejs.dev/) and [TailwindCSS](https://tailwindcss.com/).

### The _original version™_ was better, can I still use it?

Sure, although I spent a lot of time rewriting the tool from scratch so I would recommend using this version instead.

Anyway, you can download the _original version™_ from the [GitHub releases](https://github.com/alex3025/ilo-fans-controller/releases/tag/0.0.1).

### Why PHP? And why a single file? 📄

**Answer #1:**
In my opinion, PHP is perfect for this type of jobs where you need to do some server-side things and something easy to deploy (you just need a web server with PHP installed).

**Answer #2:**
I wanted to make this tool as easy as possible to install and use, so I decided to put everything in a single file.

### How can I offer you a coffee? ☕

If you found this tool useful, you can offer me a coffee using [PayPal](https://paypal.me/alex3025) or [Ko-fi](https://ko-fi.com/alex3025) to support my work! Thank you! 🙏

---

## How to install

> ⚠ **IMPORTANT!** ⚠
>
> This tool work thanks to a **[patched iLO firmware](https://www.reddit.com/r/homelab/comments/sx3ldo/hp_ilo4_v277_unlocked_access_to_fan_controls/)** that expose to the iLO SSH interface some commands to manipulate the fans speeds.
>
> **If you don't have this patch, this tool is useless.**

### Requirements

* An **HP server**, obviously
* A patched **iLO 4** firmware (as explained above)
* A Web Server with **PHP 8.1+**, the **`ssh2`** and **`curl`** extensions installed

#### The following guide was run on

* An **HP DL380e G8** server
* **iLO 4** Advanced **v2.77** (07 December 2020)
* A Proxmox container running **Ubuntu 22.04**
* Apache 2 & **PHP 8.1**

> ℹ **NOTE:** If this tool will be reachable from the Internet, remember to setup some sort of **authentication** (like Basic Auth) to prevent unauthorized access.

---

### Preparing the environment

1. Update the system:

    ```sh
    sudo apt-get update && sudo apt-get upgrade
    ```

2. Install the required packages (`git`, `apache2`, `php8.1`, `php8.1-curl` and `php8.1-ssh2`):

    ```sh
    sudo apt-get install git apache2 php8.1 php8.1-curl php8.1-ssh2
    ```

### Get the code from GitHub

We've installed `git` in the previous step, so we can use it to clone the repository:

```sh
git clone https://github.com/alex3025/ilo-fans-controller.git && cd ilo-fans-controller
```

### Configuring and installing the tool

1. Open the `config.inc.php` file you favourite text editor and edit the variables to match your configuration.

    > ℹ **NOTE:** It is recommended to create a new user on the iLO interface with the minimum privileges required to change the fans speeds.

    Here is an example:

    ```php
    <?php

    /*
    ILO ACCESS CREDENTIALS
    --------------
    These are used to connect to the iLO
    interface and manage the fan speeds.
    */

    $ILO_HOST = '192.168.1.69';
    $ILO_USERNAME = 'Administrator';
    $ILO_PASSWORD = 'AdministratorPassword1234';

    ?>
    ```

2. When you're done, create a new subdirectory in your web server root directory (usually `/var/www/html/`) and copy the `config.inc.php`, `ilo-fans-controller.php` and `favicon.ico` files inside it:

    ```sh
    sudo mkdir /var/www/html/ilo-fans-controller
    sudo cp config.inc.php ilo-fans-controller.php favicon.ico /var/www/html/ilo-fans-controller/
    ```

    Then rename `ilo-fans-controller.php` to `index.php` (to make it work without specifying the filename in the URL):

    ```sh
    sudo mv /var/www/html/ilo-fans-controller/ilo-fans-controller.php /var/www/html/ilo-fans-controller/index.php
    ```

3. That's it, iLO Fans Controller is now **installed** and **ready** to manage your fans!<br>
   You can start using it by visiting `http://<server ip>/ilo-fans-controller/` in your browser.

---

## API Documentation (WIP)

The tool exposes a simple API that can be used to:

* Get the current fan speeds from iLO
* Set the fan speeds

_There is also a way to manage the presets (get existing and add new ones) but it's not documented yet._<br>
_If you wish to do that, you can check inside the source code how that works_

> The following examples use cURL to show how to use the API, but you can use any other tool you want.

### Get the fan speeds (GET)

To use this API you need to add `?api=fans` at the end of the URL.<br>
**Example: `http://<server ip>/ilo-fans-controller/index.php?api=fans`**

<details>
<summary>JSON structure (response)</summary>

```json
{
    "Fan 1": 85,
    "Fan 2": 48,
    "Fan 3": 69,
    "Fan 4": 18,
    "Fan 5": 44,
    "Fan 6": 96
}
```

</details>

<details>
<summary>cURL example:</summary>

```sh
curl http://<server ip>/ilo-fans-controller/index.php?api=fans
```
</details>

### Set the fan speeds (POST)

<details>
<summary>JSON structure example</summary>

```json
{
    "action": "fans",
    // You can use either an object or a single number value (that will be applied to all fans):
    // Example: `fans: { ... }` or `fans: 50`
    "fans": {
        "Fan 1": 40,
        "Fan 2": 23,
        "Fan 5": 70
        // ...
    }
}
```

</details>

<details>
<summary>cURL example</summary>

```sh
curl -X POST http://<server ip>/ilo-fans-controller/index.php -H 'Content-Type: application/json' -d '{"action": "fans", "fans": 50}'
```

This command will set all fans to 50%.<br>
_I personally use this command to slow down the fans automatically when my server boots._
</details>
