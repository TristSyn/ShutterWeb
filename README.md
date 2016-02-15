# ShutterWeb
A web front end for gphoto2. Allows user to configure camera settings and take photos or a timelapse of photos.

## Requirements & Instructions
gPhoto2 is a core requirement for this software to work.This is a PHP project that is confirmed to run on Apache.  I'm using a Rikomagic stick (MK802 - Allwinner A10) but equally I'm sure a RaspberryPI would work.

I think I had to run this to get Apache/PHP to be able to send commands to gphoto2:

```html
chmod +s /usr/bin/gphoto2
```

This project was designed to run well on mobile clients as that was my use case. See below the side-by-side comparison of the desktop version and the mobile version of the same page:

[![desktopscreenshot](http://i.imgur.com/WNSNAWW.png)](http://i.imgur.com/RrHiYz9.png) [![mobilescreenshot](http://i.imgur.com/SRaDPq1.png)](http://i.imgur.com/T0bAh17.png)<br>

## Notes on use & known issues

* Raw images have not been considered as it isn't part of my usecase. Don't expect thumbnails or the image popup to work.
* A timelapse effectively blocks future calls to gphoto2 until it is complete. Behaviour of commands/system is unknown if sent during a timelapse.
* Browse is very very BETA and hasn't been tested much at all
* Focal lengths are read and saved so swapping lens' isn't handled. No current workaround other than deleting the corresponding camera setting file in the cameraconfigs folder
* Just on this, gphoto2 refers to all images via an index so if an image index is requested for a thumbnail or to view and that image's index has changed, then the image returned will not be the expected one.
* I've only got a Nikon DSLR and a Canon Point&Shoot to test.  All other brands are likely to not work properly until I can configure their respective shooting mode setting.
* "Saving to Server" during a timelapse results in the latest.jpg not updating

#TODO:

* certain settings are dependent on the value of others, e.g. setting shutter speed can only be done in shutter mode, likewise aperture in aperture mode.
* certain settings are dependant on others or do not work for certain brands/models. It'd be good to disable/hide the dropdowns to prevent confusion.
* Nice pleasant error handling
* disable commands to the camera when a timelapse is running
* deal nicely with lens changes (manual reloading perhaps)
* EXIF data through the browse feature

