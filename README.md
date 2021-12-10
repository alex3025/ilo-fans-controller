# iLO Fans Controller

<p align="center">
  <img width="800" src="screenshot.png" alt="Webpage Screenshot">
  <br>
  <i>Easily manage your HP's server fans speeds, anywhere!</i>
</p>

---

### See my comment on [r/homelab](https://www.reddit.com/r/homelab/comments/rcel73/comment/hnu3iyp/?utm_source=share&utm_medium=web2x&context=3) to know the story behind this!


## How it works

1. When you open it, the page sends a GET request to the Python "Proxy" to get the current speeds of the fans.
   
2. When you apply the settings, the page connects to iLO's SSH console and executes necessary commands to set the speeds.

3. In about 30 seconds, your server's fans will be set to the selected speed. 


## Installation

### Requirements:
* An **HP server**, obviously.
* **iLO hacked** with _[The Fan Hack](https://www.reddit.com/r/homelab/comments/hix44v/silence_of_the_fans_pt_2_hp_ilo_4_273_now_with/)_
* A web server with PHP 7.4
* Python 3.9 with `pip`

#### The following guide was run on:
* An **HP DL380e G8** server
* **iLO 4** Advanced **v2.73** (11 February 2020)
* A Proxmox LXC container running **Ubuntu 21.04**
* Apache 2

---

> The purpose of the following guide is to give an idea on how to install the script. For example, if you want to use NGINX instead of Apache, you can do it, simply instead of running an Apache command, use the suitable alternative for NGINX.

### Get the code:
If you already have `git` installed, you can clone the repo directly:

```sh
git clone https://github.com/alex3025/ilo-fans-controller.git && cd ilo-fans-controller
```

Otherwise you can download the zip using the top right green button.
> If you downloaded the zip, remember to unzip it and `cd` inside the extracted directory.

### Installing Apache 2, PHP 7.4, the `SSH2` extension and the script:
1. Install `apache2`, `php` and `libapache2-mod-php`:
    ```sh
    sudo apt-get install apache2 php libapache2-mod-php
    ```

2. Check the PHP version:
    ```sh
    php -v | grep -Po 'PHP \d.\d'
    ```

    **Must output:** `PHP 7.4`

3. Install PHP build tools:
    ```sh
    sudo apt-get install php-pear php7.4-dev
    ```

4. Install the `SSH2` extension:
    ```sh
    pecl install ssh2-alpha
    ```

5. Enable the extension in the `/etc/php/7.4/apache2/php.ini` file:
    ```diff
    ...
    ;extension=pdo_pgsql
    ;extension=pdo_sqlite
    ;extension=pgsql
    ;extension=shmop
    + extension=ssh2.so

    ; The MIBS data available in the PHP distribution must be installed.
    ...
    ```

6. Restart Apache:
    ```sh
    sudo systemctl restart apache2
    ```

7. Configuring the PHP script:
   
    Open the `ilo-fans-controller.php` file with a text editor and change the variables:

    ```php
    // iLO Credentials
    $ILO_HOST = '192.168.1.69';
    $ILO_USERNAME = 'your-ilo-username';
    $ILO_PASSWORD = 'your-ilo-password';

    // iLO Fans Proxy Address
    $ILO_FANS_PROXY_HOST = 'http://localhost:8000';
    ```

8. Copy the `ilo-fans-controller.php` file to `/var/www/html/`:
    ###### If you want you can change the destination filename to something else.
    ```sh
    sudo cp ilo-fans-controller.php /var/www/html/
    ```

### Installing the Python "Proxy":
> On most linux distributions, Python 3.9 should™ be already installed.
1. Install `pip`:
    ```
    sudo apt-get install python3-pip
    ```

2. Install all the dependencies:
    ```sh
    pip3 install -r requirements.txt
    ```

3. Configure the python script:

    Open the `ilo-fans-proxy.py` file with a text editor and change the variables:

    ```py
    # iLO Credentials
    ILO_HOST = '192.168.1.69'
    ILO_USERNAME = 'your-ilo-username'
    ILO_PASSWORD = 'your-ilo-password'
    ```

4. Test the FastAPI server:
    ```sh
    gunicorn -b 0.0.0.0:8000 -k uvicorn.workers.UvicornWorker ilo-fans-proxy:app
    ```
    > `8000` can be changed to whatever port you want (as long as it's not used by any another service), but remember to change it also in the PHP script.

    If it's working, press `CTRL+C` to terminate the process and continue with the guide.

5. Create a service to run the script automatically on startup:

    Make a new file `/etc/systemd/system/ilo-fans-proxy.service` and paste the following text in it _(don't forge to change the placeholders)_:
    
    ```ini
    [Unit]
    Description=Gunicorn instance to serve iLO Fans Proxy
    After=network.target

    [Service]
    User=<user>
    Group=www-data
    WorkingDirectory=<directory of the python script>
    ExecStart=gunicorn -b 0.0.0.0:<port> -k uvicorn.workers.UvicornWorker ilo-fans-proxy:app

    [Install]
    WantedBy=multi-user.target
    ```

    Then enable and start the service:

    ```sh
    sudo systemctl enable ilo-fans-proxy.service
    ```

    ```sh
    sudo systemctl start ilo-fans-proxy.service
    ```

## Tips & Tricks
* If you are going **to expose the web server to the public** (or you installed the script on an existing server), make sure to **add some sort of authentication** (like Basic Authentication) to avoid people making your server take off at 3 AM.

* You can set the fans speeds programmatically using an HTTP client like `cURL`:
    ```sh
    curl -X POST -H "Content-Type: application/x-www-form-urlencoded" -d "fan-0=50&fan-3=25..." http://<server ip>/ilo-fans-controller.php
    ```
    ###### If you set up Basic Authentication, just add `-u <username>:<password>` to the command.
    If the operation was successful, the response's status code is `200`.
    <br>
    Every other code means that there was an error.
