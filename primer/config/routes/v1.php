<?php

declare(strict_types=1);

use App\Http\Action\HomeAction;
use App\Http\Action\Table;
use App\Http\Action\Table\AlbumsNotFoundAction;
use App\Http\Action\Table\TracksAction;
use App\Http\Action\V1\Artist\ResetAction;
use App\Http\Action\V1\ArtistSocial\CreateAction;
use App\Http\Action\V1\ArtistSocial\DeleteAction;
use App\Http\Action\V1\ArtistSocial\GetByArtistIdAction;
use App\Http\Action\V1\ArtistSocial\GetByIdAction;
use App\Http\Action\V1\ArtistSocial\UpdateAction;
use App\Http\Action\V1\GetStatsAction;
use App\Http\Action\V1\ISRC\ISRCSearchAction;
use App\Http\Action\V1\OpenApiAction;
use App\Http\Action\V1\PlaylistTranslate\GetByPlaylistIdAction;
use App\Http\Action\V1\Spotify\GetTokensAction;
use App\Http\Action\V1\Spotify\RefreshTokenAction;
use App\Http\Action\V1\Stats\Albums\ConflictAction;
use App\Http\Action\V1\Stats\Albums\NotFoundAppleAction;
use App\Http\Action\V1\Stats\Albums\NotFoundSpotifyAction;
use App\Http\Action\V1\Stats\Albums\NotFoundTidalAction;
use App\Http\Action\V1\Stats\ArtistProblematicAction;
use App\Http\Action\V1\Stats\ArtistProblematicUpdateStatus;
use App\Http\Action\V1\Stats\ArtistProblematicUpdateUrlAction;
use App\Http\Action\V1\Stats\ArtistsAction;
use App\Http\Action\V1\Stats\PlaylistsAction;
use App\Http\Action\V1\Stats\SuggestedArtistsAction;
use App\Http\Action\V1\Stats\TrackProblematicAction;
use App\Http\Action\V1\Stats\TrackProblematicUpdateStatus;
use App\Http\Action\V1\Stats\TrackProblematicUpdateUrlAction;
use App\Http\Action\V1\Stats\Tracks;
use App\Http\Action\V1\Youtube\YoutubeSearchAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use ZayMedia\Shared\Components\Router\StaticRouteGroup as Group;

return static function (App $app): void {
    $app->get('/', HomeAction::class);
    $app->get('/table/playlists', Table\PlaylistsAction::class);
    $app->get('/table/suggested-artists', Table\SuggestedArtistsAction::class);
    $app->get('/table/artists', Table\ArtistsAction::class);
    $app->get('/table/albums', Table\AlbumsAction::class);
    $app->get('/table/albums-not-found', AlbumsNotFoundAction::class);
    $app->get('/table/tracks', TracksAction::class);
    $app->get('/table/track-problematic', Table\TrackProblematicAction::class);
    $app->get('/table/artist-problematic', Table\ArtistProblematicAction::class);

    $app->group('/v1', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('', OpenApiAction::class);
        $group->get('/youtube', YoutubeSearchAction::class);

        $group->group('/stats', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetStatsAction::class);
            $group->get('/artists', ArtistsAction::class);
            $group->get('/playlists', PlaylistsAction::class);
            $group->get('/track-problematic', TrackProblematicAction::class);
            $group->get('/artist-problematic', ArtistProblematicAction::class);
            $group->get('/suggested-artists', SuggestedArtistsAction::class);
            $group->get('/albums/conflict', ConflictAction::class);
            $group->get('/albums/not-found-spotify', NotFoundSpotifyAction::class);
            $group->get('/albums/not-found-tidal', NotFoundTidalAction::class);
            $group->get('/albums/not-found-apple', NotFoundAppleAction::class);
            $group->get('/tracks/conflict', Tracks\ConflictAction::class);
            $group->get('/tracks/not-found-spotify', Tracks\NotFoundSpotifyAction::class);
            $group->get('/tracks/not-found-tidal', Tracks\NotFoundTidalAction::class);
            $group->get('/tracks/not-found-apple', Tracks\NotFoundAppleAction::class);
            $group->put('/track-problematic/{id}/url', TrackProblematicUpdateUrlAction::class);
            $group->put('/track-problematic/status', TrackProblematicUpdateStatus::class);
            $group->put('/artist-problematic/{id}/url', ArtistProblematicUpdateUrlAction::class);
            $group->put('/artist-problematic/status', ArtistProblematicUpdateStatus::class);
        }));

        $group->group('/playlists', new Group(static function (RouteCollectorProxy $group): void {
            $group->post('', \App\Http\Action\V1\Playlist\CreateAction::class);
            $group->put('/{id}', \App\Http\Action\V1\Playlist\UpdateAction::class);
            $group->post('/{id}/reset', \App\Http\Action\V1\Playlist\ResetAction::class);
            $group->get('/{id}/translates', GetByPlaylistIdAction::class);
            $group->get('/{id}/translates/{translateId}', \App\Http\Action\V1\PlaylistTranslate\GetByIdAction::class);
            $group->post('/{id}/translates', \App\Http\Action\V1\PlaylistTranslate\CreateAction::class);
            $group->post('/{id}/translates/{translateId}', \App\Http\Action\V1\PlaylistTranslate\UpdateAction::class);
            $group->delete('/{id}/translates/{translateId}', \App\Http\Action\V1\PlaylistTranslate\DeleteAction::class);
        }));

        $group->group('/artists', new Group(static function (RouteCollectorProxy $group): void {
            $group->post('', \App\Http\Action\V1\Artist\CreateAction::class);
            $group->put('/{id}', \App\Http\Action\V1\Artist\UpdateAction::class);
            $group->post('/{id}/reset', ResetAction::class);
            $group->get('/{id}/socials', GetByArtistIdAction::class);
            $group->get('/{id}/socials/{socialId}', GetByIdAction::class);
            $group->post('/{id}/socials', CreateAction::class);
            $group->put('/{id}/socials/{socialId}', UpdateAction::class);
            $group->delete('/{id}/socials/{socialId}', DeleteAction::class);
        }));

        $group->group('/isrc', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('/{code}', ISRCSearchAction::class);
        }));

        $group->group('/spotify-tokens', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetTokensAction::class);
            $group->put('/{id}', RefreshTokenAction::class);
        }));
    }));
};
