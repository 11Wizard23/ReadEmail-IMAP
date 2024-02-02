# Email Service

This service is built using the "webklex/laravel-imap" dependency, which can be installed via Composer. The primary functionality revolves around the obtenerMensajes and marcarMensaje methods.

## Instalation

```bash
composer require webklex/laravel-imap
```

## obtenerMensajes Function

The obtenerMensajes function retrieves email messages based on specified criteria such as the folder name (e.g., 'INBOX'), read/unread status, subject filters, and the option to upload attachments to an Amazon S3 or Digital Ocean space.

Note: For S3 attachment uploading, use the companion service available in the repository.

## obtenerMensajes Function

The marcarMensaje function is used to mark a message with a specific status, such as marking it as read. It takes the folder name, message UID, and the desired status as parameters.

Feel free to explore the provided functions and tailor them to suit your specific needs. If you encounter any issues or have suggestions for improvement, please don't hesitate to reach out.