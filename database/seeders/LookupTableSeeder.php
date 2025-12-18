<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MusicType;
use App\Models\MusicKey;
use App\Models\MusicGenre;
use App\Models\MusicCreationStatus;
use App\Models\EditType;
use App\Models\FootageType;
use App\Models\ReleaseTiming;
use App\Models\Department;
use App\Models\MusicTypeCompletionDay;
use App\Models\Deliverable;

class LookupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Music Types
        $musicTypes = [
            'Original',
            'Remix Original',
            'Remix Bootleg',
            'Cover',
            'Nonstop Longform Official',
            'Nonstop Longform Bootleg',
            'Nonstop Mashup Official',
            'Nonstop Mashup',
            'Experimental',
            'Jingle',
        ];

        foreach ($musicTypes as $type) {
            MusicType::create(['name' => $type]);
        }

        // Music Creation Statuses
        $statuses = [
            'CONCEPT',
            'WAITING ON LYRICS',
            'WAITING ON VOCALS',
            'IN PRODUCTION',
            'DONE',
        ];

        foreach ($statuses as $status) {
            MusicCreationStatus::create(['name' => $status]);
        }

        // Music Keys
        $keys = [
            'C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B',
            'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'A#m', 'Bm',
        ];

        foreach ($keys as $key) {
            MusicKey::create(['name' => $key]);
        }

        // Music Genres (common genres)
        $genres = [
            'Pop', 'Rock', 'Hip Hop', 'R&B', 'Electronic', 'Dance', 'House', 'Techno',
            'Trance', 'Dubstep', 'Trap', 'Jazz', 'Classical', 'Country', 'Reggae',
            'Latin', 'K-Pop', 'Indie', 'Alternative', 'Metal', 'Punk', 'Folk',
        ];

        foreach ($genres as $genre) {
            MusicGenre::create(['name' => $genre]);
        }

        // Edit Types
        $editTypes = [
            'Music Video',
            'Lyric Video',
            'Behind the Scenes',
            'Interview',
            'Live Performance',
            'Teaser',
            'Trailer',
            'Promotional',
        ];

        foreach ($editTypes as $type) {
            EditType::create(['name' => $type]);
        }

        // Footage Types
        $footageTypes = [
            'Studio Recording',
            'Live Performance',
            'Behind the Scenes',
            'Interview',
            'Stock Footage',
            'Animation',
            'Mixed Media',
        ];

        foreach ($footageTypes as $type) {
            FootageType::create(['name' => $type]);
        }

        // Release Timings
        $releaseTimings = [
            'pre-release',
            'post-release',
            'other',
        ];

        foreach ($releaseTimings as $timing) {
            ReleaseTiming::create(['name' => $timing]);
        }

        // Music Type Completion Days (default: 7 days before release for all types)
        // This can be customized later through admin interface
        $departments = Department::all();
        $musicTypes = MusicType::all();
        
        foreach ($departments as $dept) {
            foreach ($musicTypes as $musicType) {
                // Default to 7 days before release, can be customized
                MusicTypeCompletionDay::create([
                    'music_type_id' => $musicType->id,
                    'department_id' => $dept->id,
                    'days_before_release' => 7,
                ]);
            }
        }

        // Default Deliverables per Department
        $deliverables = [
            'Music Mastering' => [
                'WAV Master',
                'MP3 Master',
                'Stem Files',
                'Instrumental',
                'Acapella',
            ],
            'Graphic Design' => [
                'Album Artwork',
                'Single Artwork',
                'Social Media Graphics',
                'Banner',
                'Logo',
            ],
            'Video Editing' => [
                'Full Video',
                'Short Form Video',
                'Vertical Video',
                'Horizontal Video',
                'Square Video',
            ],
            'Distribution - Video' => [
                'YouTube',
                'Vimeo',
                'Social Media',
            ],
            'Distribution - Graphic' => [
                'Social Media',
                'Website',
                'Print',
            ],
            'Distribution - Music' => [
                'Spotify',
                'Apple Music',
                'YouTube Music',
                'SoundCloud',
                'Bandcamp',
            ],
        ];

        foreach ($deliverables as $deptName => $items) {
            // Map department names to slugs
            $slugMap = [
                'Music Mastering' => 'music-mastering',
                'Graphic Design' => 'graphic-design',
                'Video Editing' => 'video-editing',
                'Distribution - Video' => 'distribution-video',
                'Distribution - Graphic' => 'distribution-graphic',
                'Distribution - Music' => 'distribution-music',
            ];
            
            $slug = $slugMap[$deptName] ?? strtolower(str_replace([' ', '-'], '-', $deptName));
            $dept = Department::where('slug', $slug)->first();
            if ($dept) {
                foreach ($items as $item) {
                    Deliverable::create([
                        'department_id' => $dept->id,
                        'name' => $item,
                    ]);
                }
            }
        }
    }
}
