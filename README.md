# wn-s3browser-plugin

[![Buy me a tree](https://img.shields.io/badge/Buy%20me%20a%20tree-%F0%9F%8C%B3-green)](https://ecologi.com/mik-p-online?gift-trees)
[![Plant a Tree for Production](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20a%20Tree%20for%20Production&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Ftreeware%2Ftrees)](https://plant.treeware.earth/mik-p/wn-s3browser-plugin)

S3 object browser plugin containing components for front end views to interact with an s3 bucket like a file browser. This is a plugin for [WinterCMS](https://wintercms.com).

## Why?

There is already a media manager and remote filesystem support in WinterCMS.

Although the CMS supports these features, there are not many good examples of components that show files to users. Here are some.

This plugin allows an additional location and method of storing files. It can support `unofficial` S3 implementations as well as HTTP only S3 services. This can be very useful if you want store files in S3 in a self hosted or non-public configuration. It is intended to allow front-end users to interact with stored files. It provides an API that can be modified with middleware creating application specific access control on files.

For example:

Say that your website hosts a bunch of CAD files that your users can download, but you only want some users to be able to access them. The problem further complicating the issue is that all your CAD files are on your business's NAS - which is not public and the sys-admin didn't bother to set up SSL, add valid certificates, or create root-CAs and maybe there isn't any DNS records either.

By adding a middleware to the s3browser routes only allowed files will display on the downloads page and the insecure s3 storage server is not publicly exposed.

## Issues

Admittedly I started this plugin without really understanding the laravel filesystem so I aim to migrate this implementation to use the built in remote storage provided by WinterCMS.

## Usage

Just add the various components to views.

### API

- {GET} `/api/v1/list/{bucket}`
- {POST/GET} `/api/v1/object`
- {GET} `/api/v1/download`
- {POST} `/api/v1/upload`
- {GET} `/api/v1/zip`
- {GET} `/api/v1/select`

### components

- s3browser - browse a given bucket
- s3uploader - upload to a given bucket

## Licence

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/mik-p/wn-s3browser-plugin) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.
