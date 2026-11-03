# The database image of the compose stack of photo-gallery-api.
#
# The service builds from this file instead of pointing at a registry image
# (R91). The base image is pinned by digest.

FROM mysql:26.10@sha256:3819b2b23835c4a73c8b022fafc33c8095c0f81c929a8b7a84613d2fba6c52f5

# The data directory is a bind mount from var/mysql, so the permissions of the
# mount point are set on the host side.
WORKDIR /var/lib/mysql
