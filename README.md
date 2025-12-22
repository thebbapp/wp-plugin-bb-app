# BbApp Plugin for WordPress and BBPress

BbApp is a native mobile application with push alerts, instant loading and offline mode for WordPress. Also works with BBPress.

## Getting Started

1. Download the plugin zip file [here](https://github.com/thebbapp/wp-plugin-bb-app/releases/download/release/thebbapp.zip)
2. In your WordPress admin dashboard, under `Plugins > Add Plugin > Upload Plugin`, choose `thebbapp.zip`, `Install Now` and `Activate`
3. If your content source, root forum (for BBPress), or root category (for WordPress), differ from their default, then set them above
4. Generate a .p8 file for push notifications in your Apple Developer account
5. Set `Team ID`, `Key ID`, `Private Key (.p8)`, `Bundle ID`, and `iOS App Store ID` under `Settings > BbApp > Apple Push Notification Service` and `Save Changes`

## Simplified Dependency Tree

```
wp-plugin-bb-app
│
├── Content Source
│   ├── thebbapp/content-source-wordpress
│   │   ├── thebbapp/content-source-wordpress-base
│   │   │   └── thebbapp/content-source
│   │   └── thebbapp/rest-api-wordpress
│   │       └── thebbapp/rest-api-wordpress-base
│   │           └── thebbapp/rest-api
│   └── thebbapp/content-source-bbpress
│       ├── thebbapp/content-source-wordpress-base
│       │   └── thebbapp/content-source
│       └── thebbapp/rest-api-bbpress
│           └── thebbapp/rest-api-wordpress-base
│               └── thebbapp/rest-api
│
├── Push Service
│   ├── thebbapp/push-service-wordpress
│   │   └── thebbapp/push-service-wordpress-base
│   │       ├── thebbapp/push-service
│   │       │   ├── thebbapp/result
│   │       │   └── thebbapp/content-source
│   │       ├── thebbapp/content-source
│   │       └── thebbapp/result
│   ├── thebbapp/push-service-bbpress
│   │   └── thebbapp/push-service-wordpress-base
│   │       ├── thebbapp/push-service
│   │       │   ├── thebbapp/result
│   │       │   └── thebbapp/content-source
│   │       ├── thebbapp/content-source
│   │       └── thebbapp/result
│   ├── thebbapp/push-transport-apple
│   │   ├── thebbapp/push-service
│   │   │   ├── thebbapp/result
│   │   │   └── thebbapp/content-source
│   │   ├── thebbapp/result
│   │   └── thebbapp/content-source
│   └── thebbapp/push-transport-firebase
│       ├── thebbapp/push-service
│       │   ├── thebbapp/result
│       │   └── thebbapp/content-source
│       ├── thebbapp/result
│       └── thebbapp/content-source
│
└── Smart Banner
    ├── thebbapp/smart-banner-apple
    │   └── thebbapp/smart-banner
    └── thebbapp/smart-banner-google-play
        └── thebbapp/smart-banner
```
## To keep BbApp development going, [donate here](https://thebbapp.com/donate)