# iLO Fans Controller

#### See my comment on [r/homelab](https://www.reddit.com/r/homelab/comments/rcel73/comment/hnu3iyp/?utm_source=share&utm_medium=web2x&context=3) to know the reason why I made this!


### How it works

1. To get the current speeds of the fans, the PHP script will send a GET request to the iLO Fans Proxy, which will return a JSON array with the current readings.
   
2. When you click the "Apply settings" button, the PHP script will send the necessary commands to manage the speed to the iLO SSH console.

3. The fans speeds of the server are updated. 


### Installation

#### Requirements:
  * iLO with the [fan hack](https://www.reddit.com/r/homelab/comments/hix44v/silence_of_the_fans_pt_2_hp_ilo_4_273_now_with/);
  * A web server with PHP 7.4;
  * Python 3.9 with `pip`;
  
_For this example, Ubuntu 21.04 with Apache 2 and PHP 7.4 is used._

#### To get started, clone this repository and "cd" into it.

#### iLO Fans Controller:
1. Install PHP 7.4 build tools:

    ```sh
    $ sudo apt-get install php-pear php7.4-dev
    ```

2. Install the SSH2 extension:

    ```sh
    $ sudo pecl install ssh2-alpha
    ```

3. Add the extension to the `/etc/php/7.4/apache2/php.ini` file:

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

4. Restart Apache:

    ```sh
    $ sudo systemctl restart apache2
    ```

5. Copy the `ilo-fans-controller.php` file to `/var/www/html/`:
    
    ```sh
    $ sudo cp ilo-fans-controller.php /var/www/html/
    ```

#### iLO Fans Proxy:

1. Install python requirements:
   
    ```
    $ pip3 install -r requirements.txt
    ```

2. Run the python script:

    ```sh
    $ gunicorn -w 1 -b 0.0.0.0:8000 -k uvicorn.workers.UvicornWorker main:app
    ```
    ###### `8000` can be changed to whatever port you want, only remember to change it on the php script and check if it's not used by another service.


    <details><summary><b>You can also create a service to run the script automatically on startup...</b></summary>

    Create a new file `/etc/systemd/system/ilo-fans-proxy.service` and write the following in it _(making sure to change the placeholders)_:
    
    ```ini
    [Unit]
    Description=Gunicorn instance to serve iLO Fans Proxy
    After=network.target

    [Service]
    User=<user>
    Group=www-data
    WorkingDirectory=<parent directory of the python script>
    ExecStart=gunicorn -w 1 -b 0.0.0.0:<port> -k uvicorn.workers.UvicornWorker main:app

    [Install]
    WantedBy=multi-user.target
    ```

    </details>
