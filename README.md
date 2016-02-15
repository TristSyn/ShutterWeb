# ShutterWeb
A web front end for gphoto2. Allows user to configure camera settings and take photos or a timelapse of photos.

## Requirements & Instructions
gPhoto2 is a core requirement for this software to work.This is a PHP project that is confirmed to run on Apache.  I'm using a Rikomagic stick (MK802 - Allwinner A10) but equally I'm sure a RaspberryPI would work.

I think I had to run this to get Apache/PHP to be able to send commands to gphoto2:

```html
chmod +s /usr/bin/gphoto2
```

<figure style="float:left;">
	<img src="http://i.imgur.com/RrHiYz9.png" style="height:460px; "></img>
	<figcaption>ShutterWeb as viewed on a desktop browser.</figcaption>
</figure>
[![desktopscreenshot](http://i.imgur.com/T0bAh17.png)](http://www.google.com/)

## Notes on use & known issues

	*Raw images have not been considered as it isn't part of my usecase. Don't expect thumbnails or the image popup to work.
	*A timelapse effectively blocks future calls to gphoto2 until it is complete. Behaviour of commands/system is unknown if sent during a timelapse.
	*Browse is very very BETA and hasn't been tested much at all
	*Focal lengths are read and saved so swapping lens' isn't handled. No current workaround other than deleting the corresponding camera setting file in the cameraconfigs folder
	*Just on this, gphoto2 refers to all images via an index so if an image index is requested for a thumbnail or to view and that image's index has changed, then the image returned will not be the expected one.
	<li style="display:none;">Some (most?) cameras cannot have the shooting mode changed by the software as the camera always obeys the wheel. There may be an issue with turning off in one mode, and turning back on in another.
	<li style="display:none;">For such cameras, changing the shooting mode is assumed to have been successful when it in fact hasn't been. The corresponding loaded settings may reflect the values applicable to the actual mode set on the camera.
	*I've only got a Nikon DSLR and a Canon Point&Shoot to test.  All other brands are likely to not work properly until I can configure their respective shooting mode setting.

#TODO:

	*certain settings are dependent on the value of others, e.g. setting shutter speed can only be done in shutter mode, likewise aperture in aperture mode.
	*certain settings are dependant on others or do not work for certain brands/models. It'd be good to disable/hide the dropdowns to prevent confusion.
	*Nice pleasant error handling
	*disable commands to the camera when a timelapse is running
	*deal nicely with lens changes (manual reloading perhaps)
	*EXIF data through the browse feature

