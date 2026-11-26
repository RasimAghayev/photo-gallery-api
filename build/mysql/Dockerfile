# The database image of the compose stack of photo-gallery-api.
#
# The service builds from this file instead of pointing at a registry image
# (R91). The base image is pinned by digest.

FROM mysql:26.10@sha256:9092489f49fb59c4ba6c1425c6a59f71f864f24bf4339a5a26b718adc7a8b343

# The data directory is a bind mount from var/mysql, so the permissions of the
# mount point are set on the host side.
WORKDIR /var/lib/mysql
