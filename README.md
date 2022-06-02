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
* A web server that supports PHP
* Python 3.8+ with `pip`

#### The following guide was run on:
* An **HP DL380e G8** server
* **iLO 4** Advanced **v2.73** (11 February 2020)
* A Proxmox container running **Ubuntu 21.10**
* Apache 2 & **PHP 8.0**
* Python 3.9 & pip 20.3.4

---

> The purpose of the following guide is to give an idea on how to install the script. For example, if you want to use NGINX instead of Apache, you can do it, simply instead of running an Apache command, use the suitable alternative for NGINX.

### Get the code:
If you already have `git` installed, you can clone the repo directly:

```sh
git clone https://github.com/alex3025/ilo-fans-controller.git && cd ilo-fans-controller
```

Otherwise you can download the zip using the top right green button.
> If you downloaded the zip, remember to unzip it and `cd` inside the extracted directory.

### Installing Apache, PHP and the `ssh2` extension:
1. Install `apache2`, `php`, `libapache2-mod-php`, `libssh2-1` and `php-ssh2`:
    ```sh
    sudo apt-get install apache2 php libapache2-mod-php libssh2-1 php-ssh2
    ```

2. Restart Apache:
    ```sh
    sudo systemctl restart apache2
    ```

### Configuring the PHP script:
1. Open the `ilo-fans-controller.php` file with a text editor and change the variables:

    ```php
    // iLO Credentials
    $ILO_HOST = '192.168.1.69';
    $ILO_USERNAME = 'your-ilo-username';
    $ILO_PASSWORD = 'your-ilo-password';

    // iLO Fans Proxy Address
    $ILO_FANS_PROXY_HOST = 'http://localhost:8000';

    // Number of fans present in your server
    // (most of the times you don't need to change this)
    $FANS = 6;
    ```

2. Copy the `ilo-fans-controller.php` file to `/var/www/html/`:
    ```sh
    sudo cp ilo-fans-controller.php favicon.ico /var/www/html/
    ```

### Installing `pip3` and configuring the Python script:
> On most linux distributions, Python 3.8+ should™ be already installed.<br>
> If not, there are plenty of tutorials/guides on how to install it.
1. Install `pip3`:
    ```
    sudo apt-get install python3-pip
    ```

2. Install the required dependencies:
    ```sh
    pip3 install -r requirements.txt
    ```

3. Configure the python script:

    Open the `ilo-fans-proxy.py` file with a text editor and change the variables (like before):

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
    > `8000` can be changed to whatever port you want (as long as it's not in use), but don't forget to change it also in the PHP script.

    If it's working, press `CTRL+C` to terminate the process and continue with the guide.

5. Create a service to run the script automatically on startup:

    Make a new file `/etc/systemd/system/ilo-fans-proxy.service` and paste the following text in it _(remember to change the `<placeholders>`)_:
    
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
    sudo systemctl start ilo-fans-proxy.service
    ```

## Tips & Tricks
* If you are going **to expose the web server to the public** (or you installed the script on an existing public Apache installation), make sure to **add some sort of authentication** (like Basic Authentication) to avoid people making your server take off at 3 AM.

* You can set the fans speeds programmatically sending a POST request to the PHP script:
    ```sh
    # Example (using curl)
    curl -X POST -H "Content-Type: application/x-www-form-urlencoded" -d "fan-0=50&fan-3=25..." http://<server ip>/ilo-fans-controller.php
    ```
    If the operation was successful, the response's status code is `200`.
    <br>
    Every other code means that there was an error.
