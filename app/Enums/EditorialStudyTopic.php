<?php

namespace App\Enums;

enum EditorialStudyTopic: string
{
    case TrafficSignals = 'traffic_signals';
    case RoadSigns = 'road_signs';
    case RoadMarkings = 'road_markings';
    case PedestrianSafety = 'pedestrian_safety';
    case Intersections = 'intersections';
    case RightOfWay = 'right_of_way';
    case RailroadCrossings = 'railroad_crossings';
    case ParkingAndStopping = 'parking_and_stopping';
    case SpeedLimits = 'speed_limits';
    case Expressways = 'expressways';
    case MotorcycleSafety = 'motorcycle_safety';
    case VehicleSafety = 'vehicle_safety';
    case Emergencies = 'emergencies';
    case HazardPrediction = 'hazard_prediction';
    case GeneralTrafficRules = 'general_traffic_rules';

    public function label(): string
    {
        return match ($this) {
            self::TrafficSignals => 'Traffic Signals',
            self::RoadSigns => 'Road Signs',
            self::RoadMarkings => 'Road Markings',
            self::PedestrianSafety => 'Pedestrian Safety',
            self::Intersections => 'Intersections',
            self::RightOfWay => 'Right of Way',
            self::RailroadCrossings => 'Railroad Crossings',
            self::ParkingAndStopping => 'Parking and Stopping',
            self::SpeedLimits => 'Speed Limits',
            self::Expressways => 'Expressways',
            self::MotorcycleSafety => 'Motorcycle Safety',
            self::VehicleSafety => 'Vehicle Safety',
            self::Emergencies => 'Emergencies',
            self::HazardPrediction => 'Hazard Prediction',
            self::GeneralTrafficRules => 'General Traffic Rules',
        };
    }

    /**
     * Suggest study topics from immutable source_group / metadata keywords.
     * Suggestions are not exam-eligibility classifications.
     *
     * @return list<self>
     */
    public static function proposeFromSourceGroup(string $sourceGroup, ?array $sourceMetadata = null): array
    {
        $haystack = strtolower(str_replace(['_', '-'], ' ', $sourceGroup).' '.json_encode($sourceMetadata ?? [], JSON_THROW_ON_ERROR));
        $topics = [];

        $map = [
            'traffic signals' => self::TrafficSignals,
            'traffic signal' => self::TrafficSignals,
            'signals' => self::TrafficSignals,
            'signal' => self::TrafficSignals,
            'road sign' => self::RoadSigns,
            'sign' => self::RoadSigns,
            'marking' => self::RoadMarkings,
            'pedestrian' => self::PedestrianSafety,
            'intersection' => self::Intersections,
            'right of way' => self::RightOfWay,
            'priority' => self::RightOfWay,
            'railroad' => self::RailroadCrossings,
            'level crossing' => self::RailroadCrossings,
            'parking' => self::ParkingAndStopping,
            'stopping' => self::ParkingAndStopping,
            'speed' => self::SpeedLimits,
            'expressway' => self::Expressways,
            'motorcycle' => self::MotorcycleSafety,
            'vehicle' => self::VehicleSafety,
            'upkeep' => self::VehicleSafety,
            'accident' => self::Emergencies,
            'disaster' => self::Emergencies,
            'emergency' => self::Emergencies,
            'hazard' => self::HazardPrediction,
            'overtake' => self::RightOfWay,
            'rules' => self::GeneralTrafficRules,
            'license' => self::GeneralTrafficRules,
            'jaf' => self::GeneralTrafficRules,
            'master' => self::GeneralTrafficRules,
        ];

        foreach ($map as $needle => $topic) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/', $haystack) === 1) {
                $topics[$topic->value] = $topic;
            }
        }

        if ($topics === []) {
            $topics[self::GeneralTrafficRules->value] = self::GeneralTrafficRules;
        }

        return array_values($topics);
    }
}
