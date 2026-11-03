# The database image of the compose stack of photo-gallery-api.
#
# The service builds from this file instead of pointing at a registry image
# (R91). The base image is pinned by digest.

FROM mysql:8.4@sha256:a81d043930e51745522bd5c430961cf8286f0a23f0a9a2a6e0108f7ea71b0902

# The data directory is a bind mount from var/mysql, so the permissions of the
# mount point are set on the host side.
WORKDIR /var/lib/mysql
