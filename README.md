# ShutterWeb
A web front end for gphoto2. Allows user to configure camera settings and take photos or a timelapse of photos.

Notes on use & known issues
-Raw images have not been considered as it isn't part of my usecase. Don't expect thumbnails or the image popup to work.
-A timelapse effectively blocks future calls to gphoto2 until it is complete. Behaviour of commands/system is unknown if sent during a timelapse.
-Browse is very very BETA and hasn't been tested much at all
-Focal lengths are read and saved so swapping lens' isn't handled. No current workaround other than deleting the corresponding camera setting file in the cameraconfigs folder
-Just on this, gphoto2 refers to all images via an index so if an image index is requested for a thumbnail or to view and that image's index has changed, then the image returned will not be the expected one.
-Some (most?) cameras cannot have the shooting mode changed by the software as the camera always obeys the wheel. There may be an issue with turning off in one mode, and turning back on in another.
-For such cameras, changing the shooting mode is assumed to have been successful when it in fact hasn't been. The corresponding loaded settings may reflect the values applicable to the actual mode set on the camera.
-I've only got a Nikon DSLR and a Canon Point&Shoot to test.  All other brands are likely to not work properly until I can configure their respective shooting mode setting.

TODO:
-certain settings are dependent on the value of others, e.g. setting shutter speed can only be done in shutter mode, likewise aperture in aperture mode.
-certain settings are dependant on others or do not work for certain brands/models. It'd be good to disable/hide the dropdowns to prevent confusion.
-Nice pleasant error handling
-disable commands to the camera when a timelapse is running
-deal nicely with lens changes (manual reloading perhaps)
