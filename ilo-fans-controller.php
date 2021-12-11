<?php
// iLO Credentials
$ILO_HOST = '192.168.1.69';
$ILO_USERNAME = 'your-ilo-username';
$ILO_PASSWORD = 'your-ilo-password';

// iLO Fans Proxy Address
$ILO_FANS_PROXY_HOST = 'http://localhost:8000';


$raw_fan_speeds = file_get_contents($ILO_FANS_PROXY_HOST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fan_count = 0;
  foreach ($_POST as $key => $value)
    if (strpos($key, 'fan-') === 0)
      $fan_count++;

  if ($fan_count > 0) {
    $connection = ssh2_connect($ILO_HOST, 22);
    ssh2_auth_password($connection, $ILO_USERNAME, $ILO_PASSWORD);

    $current_fan_speeds = json_decode($raw_fan_speeds, true);
    $new_speeds = $current_fan_speeds;
    foreach ($_POST as $key => $value)
      if (strpos($key, 'fan-') === 0) {          
        $fan = intval($_POST[$key]);
        $index = str_replace('fan-', '', $key);

        if (($fan >= 10 && $fan <= 100) && $fan != $current_fan_speeds[$index]) {
          $stream = ssh2_exec($connection, 'fan p ' . $index . ' min 255');
          stream_set_blocking($stream, true);
          stream_get_contents($stream);

          $stream = ssh2_exec($connection, 'fan p ' . $index . ' max ' . ceil($fan / 100 * 255));
          stream_set_blocking($stream, true);
          stream_get_contents($stream);
        }

        $new_speeds[$index] = $fan;
      }

    while ($new_speeds != $current_fan_speeds) {
      $raw_fan_speeds = file_get_contents($ILO_FANS_PROXY_HOST);
      $current_fan_speeds = json_decode($raw_fan_speeds, true);
    }
  }
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="./favicon.ico">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    <title>iLO Fans Controller</title>
  </head>
  <body class="p-5 sm:p-10">
    <div class="flex flex-col sm:flex-row justify-center items-center sm:justify-start mb-4 sm:mb-7 space-x-2">
      <img src="./favicon.ico" alt="favicon" class="h-10 w-10 mr-1">
      <h1 class="font-bold text-3xl">
        iLO Fans Controller
      </h1>
      <a href="https://alex3025.tk" class="text-sm font-normal sm:self-end mb-0.5 italic">by alex3025</a>
    </div>

    <div class="flex flex-col sm:flex-row items-center">
      <p class="text-gray-400 select-none sm:mr-3 mb-3 sm:mb-0">
        Presets:
      </p>

      <div class="space-x-2">
        <button class="border border-green-400 hover:border-green-500 rounded px-1 text-sm text-green-400 hover:text-green-500 focus:text-green-500 focus:border-green-500 select-none" id="silent-preset">
          Silent Mode
        </button>
        <button class="border border-green-400 hover:border-green-500 rounded px-1 text-sm text-green-400 hover:text-green-500 focus:text-green-500 focus:border-green-500 select-none" id="normal-preset">
          Normal Mode
        </button>
        <button class="border border-green-400 hover:border-green-500 rounded px-1 text-sm text-green-400 hover:text-green-500 focus:text-green-500 focus:border-green-500 select-none" id="turbo-preset">
          Turbo Mode
        </button>
      </div>
    </div>

    <form method="post" action="" class="mt-7">
      <div id="fans" class="space-y-2 w-full">
        <div class="space-y-3 w-full sm:max-w-max">
          <div class="flex items-center justify-between space-x-3">
            <label for="fan-0-number" class="font-medium text-md sm:text-lg w-14 no-break">Fan #0</label>
            <input type="range" min="10" max="100" value="0" class="w-full sm:w-72 flex-1">
            <input type="number" min="10" max="100" name="fan-0" id="fan-0-number" placeholder="0" required class="bg-gray-100 border hover:border-gray-300 focus:border-gray-300 max-w-max p-1.5 py-1 rounded-md font-mono focus:outline-none">
            <button type="button" class="border border-green-400 hover:border-green-500 rounded px-1 text-sm text-green-400 hover:text-green-500 select-none">Reset</button>
          </div>
        </div>
      </div>

      <button type="button" class="mt-7 h-9 items-center w-full flex justify-center sm:w-36 bg-green-500 focus:ring-2 ring-green-200 ring-opacity-70 hover:bg-green-400 px-5 py-1.5 rounded text-white font-medium select-none" id="apply-settings">
        Apply settings
      </button>
    </form>

    <script lang="javascript">
      const current_fan_speeds = <?php echo $raw_fan_speeds; ?>;
      const form = document.querySelector('form');

      function onFormSubmit() {
        document.querySelectorAll('input').forEach(input => input.setAttribute('disabled', true));

        document.querySelectorAll('button').forEach(button => {
          button.setAttribute('disabled', true);
          button.classList.add('opacity-50');
        });

        document.querySelectorAll('.cursor-pointer, button, input[type="range"]').forEach(el => {
          el.classList.remove('cursor-pointer');
          el.classList.add('cursor-default');
        });

        const button = document.querySelector('#apply-settings')
        button.classList.add('opacity-50');
        button.disabled = true;
        button.innerHTML = `
          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="w-5 h-5" viewBox="0 0 50 50" xml:space="preserve">
            <path fill="#ffffff" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z">
              <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" />
            </path>
          </svg>
        `;
      };

      document.querySelector('#apply-settings').addEventListener('click', () => {
        if (form.checkValidity()) {
          form.submit();
          onFormSubmit();
        } else
          form.reportValidity();
      });

      for (let i = 1; i < 6; i++) {
        const template = document.querySelector('#fans > div:first-child');
        const clone = template.cloneNode(true);
        clone.querySelector('label').innerText = `Fan #${i}`;
        clone.querySelector('label').setAttribute('for', `fan-${i}-number`);
        
        clone.querySelector('input[type="number"]').id = `fan-${i}-number`;
        clone.querySelector('input[type="number"]').name = `fan-${i}`;
        document.querySelector('#fans').appendChild(clone);
      }

      let fanIndex = 0;
      document.querySelectorAll('#fans > div').forEach(fan => {
        const slider = fan.querySelector('input[type="range"]');
        const number = fan.querySelector('input[type="number"]');

        slider.removeAttribute('disabled');
        slider.classList.add('cursor-pointer');

        number.removeAttribute('disabled');

        slider.value = current_fan_speeds[fanIndex];
        number.value = current_fan_speeds[fanIndex];

        slider.addEventListener('input', () => number.value = slider.value);
        number.addEventListener('input', () => slider.value = number.value);

        fan.querySelector('button').addEventListener('click', () => {
          const id = number.id.split('-')[1];
          slider.value = current_fan_speeds[id];
          number.value = current_fan_speeds[id];
        });

        fanIndex++;
      })

      function setGlobalSpeed(value) {
        document.querySelectorAll('#fans > div > div > input[type="range"]').forEach(slider => slider.value = value);
        document.querySelectorAll('#fans > div > div > input[type="number"]').forEach(number => number.value = value);
      }

      // Presets (you can change the speed of each preset here)
      document.querySelector('#silent-preset').addEventListener('click', () => {
        setGlobalSpeed(15);
      });
      document.querySelector('#normal-preset').addEventListener('click', () => {
        setGlobalSpeed(50);
      });
      document.querySelector('#turbo-preset').addEventListener('click', () => {
        setGlobalSpeed(100);
      });
    </script>
  </body>
</html>
