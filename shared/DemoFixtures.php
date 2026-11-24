<?php
declare(strict_types=1);
/**
 * Fictional event and simulated venue-capacity fixtures for coursework ONLY.
 * The venue names/floor numbers are from the supplied SICC floor directory.
 * capacities here are invented DEMO figures, explicitly NOT official SICC limits.
 */
function fiveDemoEventFixtures(): array {
    return [
        [
            'title'=>'Sabah Innovation & Robotics Expo (fictional)',
            'description'=>'A fictional hands-on robotics exhibition with prototype demonstrations, beginner-friendly showcases and talks about emerging technology in Sabah. Academic sample event only.',
            'tag'=>'Technology','venue'=>'Kinabatangan I','floor'=>2,'demo_capacity'=>120,'days_ahead'=>25,
            'sessions'=>[['Morning Expo',10,12],['Afternoon Expo',15,17]],
            'categories'=>[['Standard','paid','30.00'],['Student','paid','18.00'],['Free Community Pass','free','0.00']],
        ],
        [
            'title'=>'Borneo Creative Market & Makers Fair (fictional)',
            'description'=>'A fictional SICC creative market featuring local-inspired arts, stationery, independent craft stalls and short design workshops. Academic sample event only.',
            'tag'=>'Exhibition','venue'=>'Jesselton Square (Open Plaza)','floor'=>3,'demo_capacity'=>150,'days_ahead'=>32,
            'sessions'=>[['Creative Market',11,16]],
            'categories'=>[['General Admission','paid','12.00'],['Youth Entry','paid','8.00']],
        ],
        [
            'title'=>'Sabah Performing Arts Evening (fictional)',
            'description'=>'A fictional concert-style evening programme featuring student ensembles, dance showcases and community performing arts at SICC. Academic sample event only.',
            'tag'=>'Performance','venue'=>'Sipadan I-III','floor'=>4,'demo_capacity'=>160,'days_ahead'=>39,
            'sessions'=>[['Evening Performance',19,21]],
            'categories'=>[['Standard','paid','65.00'],['Premium','paid','110.00']],
        ],
        [
            'title'=>'Sabah Digital Creators Conference (fictional)',
            'description'=>'A fictional one-day conference with content creation, media technology, career development and responsible online publishing discussions. Academic sample event only.',
            'tag'=>'Conference','venue'=>'Kadamaian','floor'=>5,'demo_capacity'=>110,'days_ahead'=>46,
            'sessions'=>[['Morning Conference',9,12],['Afternoon Workshops',15,18]],
            'categories'=>[['Conference Pass','paid','45.00'],['Student Pass','paid','25.00']],
        ],
        [
            'title'=>'Sustainable Sabah Community Forum (fictional)',
            'description'=>'A fictional community learning event about coastal stewardship, accessible innovation and environmental ideas in Sabah. Academic sample event only.',
            'tag'=>'Community','venue'=>'Mesilau','floor'=>5,'demo_capacity'=>90,'days_ahead'=>53,
            'sessions'=>[['Community Forum',10,13]],
            'categories'=>[['Free Community Entry','free','0.00'],['Supporter Entry','paid','20.00']],
        ],
    ];
}
