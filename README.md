# TRMNL-AtmoEst

A TRMNL plugin displaying 24-hour air quality measurements from Atmo Grand Est monitoring stations.

The plugin retrieves hourly measurements, generates SVG graphs optimized for the four TRMNL layouts, and allows the user to select a pollutant and a compatible monitoring station.

## Features

- 24-hour air quality history
- Dynamic pollutant and station selection
- Stations filtered according to pollutant availability
- Four TRMNL layouts:
  - Full
  - Half Horizontal
  - Half Vertical
  - Quadrant
- Layout-specific graph dimensions and axis rendering
- Automatic Y-axis scaling
- Local measurement cache to reduce requests to the upstream data service
- SVG output suitable for TRMNL e-ink displays

## Supported pollutants

| Pollutant | Parameter |
|---|---|
| PM10 | `pm10` |
| PM2.5 | `pm25` |
| O₃ | `o3` |
| NO₂ | `no2` |

Measurements are displayed in µg/m³.

The list of available stations depends on the selected pollutant.

## Data source

Air quality observations and monitoring-station metadata are provided by Atmo Grand Est.

The plugin retrieves measurement data on demand and caches station measurements locally before generating the TRMNL output.

## TRMNL configuration

The plugin uses two configuration fields:

1. **Pollutant**
2. **Station**

The pollutant should be selected first. The station selector is then populated with stations measuring that pollutant.

Example:

```text
Pollutant: O₃
Station: Strasbourg — Strasbourg Nord (fond)
```

The corresponding polling URL is:

```text
https://atmo.inscog.eu/?station=FR16029&pollutant=o3
```

## Public endpoints

### Plugin endpoint

```text
GET /?station={station_id}&pollutant={pollutant}
```

Example:

```text
https://atmo.inscog.eu/?station=FR16029&pollutant=o3
```

The endpoint returns the data used by the TRMNL markup, including the graph variants required for the different layouts.

### Pollutant selector

```text
GET /pollutants.php
```

Example response:

```json
[
  {"PM10": "pm10"},
  {"PM2.5": "pm25"},
  {"O₃": "o3"},
  {"NO₂": "no2"}
]
```

### Station selector

```text
GET /stations.php?pollutant=o3
```

The endpoint returns only stations supporting the requested pollutant.

Example response:

```json
[
  {"Bazeilles (industrielle)": "FR14058"},
  {"Strasbourg — Strasbourg Nord (fond)": "FR16029"}
]
```

## Requirements

- PHP >= 8.3
- PHP cURL extension
- Composer

Development dependencies include PHPUnit 12.

## Installation

Clone the repository:

```bash
git clone https://github.com/lbonnefond/TRMNL-AtmoEst.git
cd TRMNL-AtmoEst
```

Install dependencies:

```bash
composer install
```

For a production deployment:

```bash
composer install --no-dev --optimize-autoloader
```

Create the measurement cache directory:

```bash
mkdir -p var/cache/measurements
```

The PHP process must have write access to this directory.

## Local development

The PHP built-in web server can be used for local testing:

```bash
php -S 127.0.0.1:8080 -t public
```

The plugin can then be tested with:

```text
http://127.0.0.1:8080/?station=FR16029&pollutant=o3
```

The selector endpoints are available at:

```text
http://127.0.0.1:8080/pollutants.php
http://127.0.0.1:8080/stations.php?pollutant=o3
```

Development and diagnostic scripts are located in `dev/`.

## Architecture

```text
TRMNL-AtmoEst/
├── public/
│   ├── index.php
│   ├── pollutants.php
│   └── stations.php
│
├── src/
│   ├── AtmoGrandEstClient.php
│   ├── GraphDataGenerator.php
│   ├── GraphLayout.php
│   ├── GraphScaler.php
│   ├── Measurement.php
│   ├── MeasurementCache.php
│   ├── MeasurementDataset.php
│   ├── MeasurementProvider.php
│   ├── NiceYAxisScale.php
│   ├── PluginConfiguration.php
│   ├── PluginController.php
│   ├── Pollutant.php
│   ├── PollutantRegistry.php
│   ├── RequestConfigurationFactory.php
│   ├── Station.php
│   ├── StationProvider.php
│   ├── SvgGraphRenderer.php
│   ├── XAxisRenderer.php
│   ├── XAxisTick.php
│   ├── YAxisRenderer.php
│   └── Trmnl/
│       └── HtmlRenderer.php
│
├── dev/
│   └── test-*.php
│
├── composer.json
├── composer.lock
└── .gitignore
```

### Main components

**`AtmoGrandEstClient`**  
Communicates with the Atmo Grand Est data service and retrieves measurements and station metadata.

**`StationProvider`**  
Provides the monitoring stations available to the plugin and filters them according to pollutant support.

**`MeasurementProvider`**  
Coordinates measurement retrieval and caching.

**`MeasurementCache`**  
Caches measurement datasets locally to reduce upstream requests and improve response time.

**`PollutantRegistry`**  
Defines the pollutants supported by the plugin.

**`GraphDataGenerator` / `GraphScaler`**  
Prepare measurement data and graph coordinates for rendering.

**`NiceYAxisScale`**  
Calculates readable Y-axis limits and tick intervals from the measurement range.

**`SvgGraphRenderer`**  
Produces SVG graphs optimized for each TRMNL layout.

**`PluginController`**  
Coordinates configuration, measurement retrieval and generation of the plugin response.

## Deployment

The production instance is currently deployed at:

```text
https://atmo.inscog.eu/
```

A typical update consists of:

```bash
git pull
composer install --no-dev --optimize-autoloader
```

Server-specific files such as `.htaccess` are intentionally excluded from the repository.

Runtime cache files and development-generated SVG, HTML and JSON files are also excluded.

## License

The backend code in this repository is licensed under the MIT License.
See [LICENSE](LICENSE) for details.

The TRMNL plugin definition is maintained separately through TRMNL
and is subject to the applicable TRMNL community plugin publication terms.
