<?php

/**
 * Controlled private translation drafts for menkyo_original_karimen_pilot_001 (Task 46).
 *
 * Japanese remains authoritative. These drafts are AI-assisted and NOT native-reviewed.
 * Candidates :1 and :8 are withheld until clarification/revision decisions are recorded.
 *
 * @return array{
 *     collection_key: string,
 *     drafting_method: string,
 *     target_locales: list<string>,
 *     terminology: array<string, array<string, string>>,
 *     withheld: list<array{candidate_id: string, number: int, reason: string}>,
 *     translations: array<string, array<string, array{question_text: string, explanation: string}>>
 * }
 */
return [
    'collection_key' => 'menkyo_original_karimen_pilot_001',
    'drafting_method' => 'ai_assisted_controlled_draft_task_46',
    'target_locales' => ['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'],
    'terminology' => [
        '一時停止' => [
            'en' => 'temporary stop (一時停止)',
            'zh' => '临时停车（一時停止）',
            'vi' => 'dừng tạm thời (一時停止)',
            'fil' => 'pansamantalang paghinto (一時停止)',
            'ne' => 'अस्थायी रोक (一時停止)',
            'bn' => 'সাময়িক থামা (一時停止)',
            'ko' => '일시정지(一時停止)',
        ],
        '徐行' => [
            'en' => 'slow travel / proceed slowly (徐行)',
            'zh' => '缓行（徐行）',
            'vi' => 'đi chậm (徐行)',
            'fil' => 'mabagal na pagpatuloy (徐行)',
            'ne' => 'बिस्तारै चलाउने (徐行)',
            'bn' => 'ধীরে চলা (徐行)',
            'ko' => '서행(徐行)',
        ],
        '横断歩道' => [
            'en' => 'pedestrian crossing / crosswalk (横断歩道)',
            'zh' => '人行横道（横断歩道）',
            'vi' => 'vạch sang đường dành cho người đi bộ (横断歩道)',
            'fil' => 'tawiran ng pedestrian (横断歩道)',
            'ne' => 'पदयात्री क्रसिङ (横断歩道)',
            'bn' => 'পথচারী ক্রসিং (横断歩道)',
            'ko' => '횡단보도(横断歩道)',
        ],
        '踏切' => [
            'en' => 'railroad crossing / level crossing (踏切)',
            'zh' => '铁路道口（踏切）',
            'vi' => 'đường cắt đường sắt (踏切)',
            'fil' => 'tawiran ng riles (踏切)',
            'ne' => 'रेल क्रसिङ (踏切)',
            'bn' => 'রেল ক্রসিং (踏切)',
            'ko' => '건널목(踏切)',
        ],
        '優先道路' => [
            'en' => 'priority road (優先道路)',
            'zh' => '优先道路（優先道路）',
            'vi' => 'đường ưu tiên (優先道路)',
            'fil' => 'prayoridad na daan (優先道路)',
            'ne' => 'प्राथमिकता सडक (優先道路)',
            'bn' => 'অগ্রাধিকার সড়ক (優先道路)',
            'ko' => '우선도로(優先道路)',
        ],
        '最高速度' => [
            'en' => 'maximum speed (最高速度)',
            'zh' => '最高速度（最高速度）',
            'vi' => 'tốc độ tối đa (最高速度)',
            'fil' => 'pinakamataas na bilis (最高速度)',
            'ne' => 'अधिकतम गति (最高速度)',
            'bn' => 'সর্বোচ্চ গতি (最高速度)',
            'ko' => '최고속도(最高速度)',
        ],
        '安全運転義務' => [
            'en' => 'duty of safe driving (安全運転義務)',
            'zh' => '安全驾驶义务（安全運転義務）',
            'vi' => 'nghĩa vụ lái xe an toàn (安全運転義務)',
            'fil' => 'tungkulin ng ligtas na pagmamaneho (安全運転義務)',
            'ne' => 'सुरक्षित ड्राइभिङ कर्तव्य (安全運転義務)',
            'bn' => 'নিরাপদ চালনার দায়িত্ব (安全運転義務)',
            'ko' => '안전운전 의무(安全運転義務)',
        ],
        '交差点' => [
            'en' => 'intersection (交差点)',
            'zh' => '交叉路口（交差点）',
            'vi' => 'giao lộ (交差点)',
            'fil' => 'interseksyon (交差点)',
            'ne' => 'चौबाटो (交差点)',
            'bn' => 'সংযোগস্থল (交差点)',
            'ko' => '교차로(交差点)',
        ],
        '道路標識' => [
            'en' => 'road sign (道路標識)',
            'zh' => '道路标志（道路標識）',
            'vi' => 'biển báo giao thông (道路標識)',
            'fil' => 'palatandaan sa daan (道路標識)',
            'ne' => 'सडक चिन्ह (道路標識)',
            'bn' => 'সড়ক চিহ্ন (道路標識)',
            'ko' => '도로표지(道路標識)',
        ],
    ],
    'withheld' => [
        [
            'candidate_id' => 'menkyo_original_karimen_pilot_001:1',
            'number' => 1,
            'reason' => 'Research assessment supported_with_clarification: pedestrian-crossing / Art. 7 subject-scope clarification remains unresolved; no human source decision recorded.',
        ],
        [
            'candidate_id' => 'menkyo_original_karimen_pilot_001:8',
            'number' => 8,
            'reason' => 'Research assessment supported_with_clarification: proposed Art. 36(1)(i) streetcar-aware stem revision not applied; await recorded source-version decision before drafting translations.',
        ],
    ],
    'translations' => [
        'menkyo_original_karimen_pilot_001:2' => [
            'en' => [
                'question_text' => 'At an intersection where traffic is not controlled, or immediately before such an intersection, when temporary stop (一時停止) is designated by a road sign or marking, you must make a temporary stop immediately before the stop line indicated by the sign or marking (or immediately before the intersection if there is no stop line).',
                'explanation' => 'Road Traffic Act Article 43 requires a temporary stop immediately before the designated stop line (or before the intersection if none). Slow travel (徐行) alone is not enough.',
            ],
            'zh' => [
                'question_text' => '在未实行交通管制的交叉路口或其紧前方，道路标志等指定应当临时停车（一時停止）时，必须在标志等所示的停止线紧前方（没有停止线时则在交叉路口紧前方）临时停车。',
                'explanation' => '道路交通法第43条规定，在指定的临时停车处，必须在停止线（或交叉路口）紧前方临时停车。仅缓行（徐行）不够。',
            ],
            'vi' => [
                'question_text' => 'Tại giao lộ không được điều khiển giao thông, hoặc ngay trước giao lộ đó, khi biển báo/đánh dấu đường chỉ định phải dừng tạm thời (一時停止), phải dừng tạm thời ngay trước vạch dừng theo biển báo (hoặc ngay trước giao lộ nếu không có vạch dừng).',
                'explanation' => 'Điều 43 Luật Giao thông đường bộ Nhật Bản yêu cầu dừng tạm thời ngay trước vạch dừng được chỉ định (hoặc trước giao lộ nếu không có). Chỉ đi chậm (徐行) thì chưa đủ.',
            ],
            'fil' => [
                'question_text' => 'Sa isang interseksyon na walang traffic control, o kaagad bago ito, kapag may palatandaan sa daan na nagtatakda ng pansamantalang paghinto (一時停止), dapat kang tumigil pansamantala kaagad bago ang stop line (o kaagad bago ang interseksyon kung walang stop line).',
                'explanation' => 'Ang Artikulo 43 ng Road Traffic Act ay nangangailangan ng pansamantalang paghinto kaagad bago ang itinalagang stop line (o bago ang interseksyon kung wala). Ang mabagal na pagpatuloy (徐行) lamang ay hindi sapat.',
            ],
            'ne' => [
                'question_text' => 'यातायात नियन्त्रण नभएको चौबाटो वा त्यसको ठीक अगाडि, सडक चिन्ह आदिले अस्थायी रोक (一時停止) तोकेको छ भने, चिन्हले देखाएको रोक रेखाको ठीक अगाडि (रोक रेखा नभए चौबाटोको ठीक अगाडि) अस्थायी रोक गर्नुपर्छ।',
                'explanation' => 'सडक यातायात ऐन दफा ४३ ले तोकिएको रोक रेखा (वा चौबाटो) ठीक अगाडि अस्थायी रोक अनिवार्य गर्छ। बिस्तारै चलाउने (徐行) मात्र पर्याप्त होइन।',
            ],
            'bn' => [
                'question_text' => 'যে সংযোগস্থলে যানবাহন নিয়ন্ত্রণ নেই, বা তার ঠিক আগে, সড়ক চিহ্ন ইত্যাদি দ্বারা সাময়িক থামা (一時停止) নির্ধারিত থাকলে, চিহ্নের স্টপ লাইনের ঠিক আগে (স্টপ লাইন না থাকলে সংযোগস্থলের ঠিক আগে) সাময়িক থামতে হবে।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ৪৩ নির্ধারিত স্টপ লাইন (বা সংযোগস্থল) এর ঠিক আগে সাময়িক থামা বাধ্যতামূলক করে। শুধু ধীরে চলা (徐行) যথেষ্ট নয়।',
            ],
            'ko' => [
                'question_text' => '교통 정리가 이루어지지 않는 교차로 또는 그 직전에서, 도로표지 등에 의해 일시정지(一時停止)가 지정되어 있는 때에는, 표지 등에 의한 정지선의 직전(정지선이 없을 때에는 교차로의 직전)에서 일시정지해야 한다.',
                'explanation' => '도로교통법 제43조는 지정된 일시정지 장소에서 정지선(또는 교차로) 직전에 일시정지할 의무를 정한다. 서행(徐行)만으로는 부족하다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:3' => [
            'en' => [
                'question_text' => 'When a pedestrian or similar road user is crossing, or about to cross, ahead of your path at a pedestrian crossing (横断歩道) or similar facility, a vehicle must make a temporary stop immediately before that crossing and must not impede their passage.',
                'explanation' => 'Road Traffic Act Article 38(1) requires stopping and not impeding pedestrians and similar users who are crossing or about to cross ahead at a crosswalk or similar facility.',
            ],
            'zh' => [
                'question_text' => '当有行人等正在或正要通过人行横道（横断歩道）等横断其前进方向前方时，车辆等必须在该人行横道等紧前方临时停车，并且不得妨碍其通行。',
                'explanation' => '道路交通法第38条第1项规定，在人行横道等处有行人等正在或正要横断前方时，必须临时停车且不得妨碍其通行。',
            ],
            'vi' => [
                'question_text' => 'Khi có người đi bộ hoặc đối tượng tương tự đang hoặc sắp băng qua phía trước đường đi của bạn tại vạch sang đường (横断歩道) hoặc cơ sở tương tự, phương tiện phải dừng tạm thời ngay trước chỗ đó và không được cản trở việc đi lại của họ.',
                'explanation' => 'Điều 38 khoản 1 Luật Giao thông đường bộ Nhật Bản yêu cầu dừng và không cản trở người đi bộ/đối tượng tương tự đang hoặc sắp băng qua phía trước tại vạch sang đường.',
            ],
            'fil' => [
                'question_text' => 'Kapag may pedestrian o katulad na gumagamit ng daan na tumatawid o magtatangkaing tumawid sa unahan ng iyong landas sa tawiran (横断歩道) o katulad na pasilidad, ang sasakyan ay dapat tumigil pansamantala kaagad bago ang tawiran at hindi hadlangan ang kanilang pagdaan.',
                'explanation' => 'Ang Artikulo 38(1) ng Road Traffic Act ay nangangailangan ng paghinto at hindi paghadlang sa mga pedestrian at katulad na gumagamit na tumatawid o magtatangkaing tumawid sa unahan sa tawiran.',
            ],
            'ne' => [
                'question_text' => 'पदयात्री क्रसिङ (横断歩道) वा यस्तै सुविधाबाट आफ्नो बाटोको अगाडि पार गर्दै वा पार गर्न खोज्दै गरेका पदयात्री आदि भएमा, सवारीले उक्त क्रसिङको ठीक अगाडि अस्थायी रोक गर्नुपर्छ र उनीहरूको आवागमन बाधा पार्नु हुँदैन।',
                'explanation' => 'सडक यातायात ऐन दफा ३८(१) ले क्रसिङमा अगाडि पार गर्दै/गर्न खोज्दै गरेका पदयात्री आदिका लागि रोक र बाधा नपार्ने कर्तव्य तोक्छ।',
            ],
            'bn' => [
                'question_text' => 'পথচারী ক্রসিং (横断歩道) বা অনুরূপ সুবিধা দিয়ে আপনার পথের সামনে অতিক্রম করছেন বা করতে যাচ্ছেন এমন পথচারী ইত্যাদি থাকলে, যানবাহনকে সেই ক্রসিংয়ের ঠিক আগে সাময়িক থামতে হবে এবং তাদের চলাচলে বাধা দেওয়া যাবে না।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ৩৮(১) ক্রসিংয়ে সামনে অতিক্রমরত/অতিক্রম করতে যাওয়া পথচারী ইত্যাদির জন্য থামা এবং বাধা না দেওয়ার বাধ্যবাধকতা নির্ধারণ করে।',
            ],
            'ko' => [
                'question_text' => '횡단보도(横断歩道) 등에 의해 진로 전방을 횡단하거나 횡단하려고 하는 보행자 등이 있을 때에는, 차량 등은 해당 횡단보도 등의 직전에 일시정지하고, 또한 그 통행을 방해하지 않도록 해야 한다.',
                'explanation' => '도로교통법 제38조 제1항은 횡단보도 등에서 진로 전방을 횡단하거나 횡단하려는 보행자 등이 있을 때 일시정지하고 통행을 방해하지 않을 의무를 정한다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:4' => [
            'en' => [
                'question_text' => 'When a vehicle is about to pass a railroad crossing (踏切), it must stop immediately before the crossing (or immediately before the stop line if there is one) and may proceed only after confirming that it is safe. However, when proceeding in accordance with a traffic signal, it may proceed without stopping immediately before the crossing.',
                'explanation' => 'Road Traffic Act Article 33(1) sets the stop-and-confirm rule at railroad crossings, with an exception when following a traffic signal.',
            ],
            'zh' => [
                'question_text' => '车辆等要通过铁路道口（踏切）时，必须在道口紧前方（有停止线时在其紧前方）停车，并在确认安全后方可前进。但按照信号机显示的信号通行时，可以不在道口紧前方停车而前进。',
                'explanation' => '道路交通法第33条第1项规定铁路道口前的停车与安全确认原则，并规定按照信号通行时的例外。',
            ],
            'vi' => [
                'question_text' => 'Khi phương tiện sắp qua đường cắt đường sắt (踏切), phải dừng ngay trước đường cắt (hoặc ngay trước vạch dừng nếu có) và chỉ được đi tiếp sau khi xác nhận an toàn. Tuy nhiên, khi đi theo tín hiệu đèn giao thông thì có thể đi tiếp mà không dừng ngay trước đường cắt.',
                'explanation' => 'Điều 33 khoản 1 Luật Giao thông đường bộ Nhật Bản quy định dừng và xác nhận an toàn tại đường cắt đường sắt, kèm ngoại lệ khi tuân theo tín hiệu đèn.',
            ],
            'fil' => [
                'question_text' => 'Kapag ang sasakyan ay tatawid sa tawiran ng riles (踏切), dapat itong tumigil kaagad bago ang tawiran (o kaagad bago ang stop line kung mayroon) at maaari lamang magpatuloy pagkatapos tiyaking ligtas. Gayunpaman, kapag sumusunod sa traffic signal, maaari itong magpatuloy nang hindi tumitigil kaagad bago ang tawiran.',
                'explanation' => 'Ang Artikulo 33(1) ng Road Traffic Act ay nagtatakda ng paghinto at pagtiyak ng kaligtasan sa tawiran ng riles, may exception kapag sumusunod sa traffic signal.',
            ],
            'ne' => [
                'question_text' => 'सवारी रेल क्रसिङ (踏切) पार गर्न लाग्दा, क्रसिङको ठीक अगाडि (रोक रेखा भए त्यसको ठीक अगाडि) रोकिनुपर्छ र सुरक्षित भएको पुष्टि गरेपछि मात्र अगाडि बढ्न सकिन्छ। तर ट्राफिक संकेतअनुसार जाँदा क्रसिङको ठीक अगाडि नरोकी अगाडि बढ्न सकिन्छ।',
                'explanation' => 'सडक यातायात ऐन दफा ३३(१) ले रेल क्रसिङमा रोक र सुरक्षा पुष्टि गर्ने नियम तोक्छ; संकेतअनुसार जाँदा अपवाद छ।',
            ],
            'bn' => [
                'question_text' => 'যানবাহন রেল ক্রসিং (踏切) অতিক্রম করতে যাওয়ার সময়, ক্রসিংয়ের ঠিক আগে (স্টপ লাইন থাকলে তার ঠিক আগে) থামতে হবে এবং নিরাপদ নিশ্চিত করার পরেই এগোতে পারবে। তবে ট্রাফিক সিগন্যাল অনুসরণ করে চললে ক্রসিংয়ের ঠিক আগে না থেমে এগোতে পারে।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ৩৩(১) রেল ক্রসিংয়ে থামা ও নিরাপত্তা নিশ্চিতকরণের নিয়ম নির্ধারণ করে; সিগন্যাল অনুসরণের ক্ষেত্রে ব্যতিক্রম আছে।',
            ],
            'ko' => [
                'question_text' => '차량 등은 건널목(踏切)을 통과하려고 할 때에는 건널목의 직전(정지선이 있을 때에는 그 직전)에서 정지하고, 또한 안전한 것을 확인한 후가 아니면 진행해서는 안 된다. 다만, 신호기가 표시하는 신호에 따를 때에는 건널목의 직전에 정지하지 않고 진행할 수 있다.',
                'explanation' => '도로교통법 제33조 제1항은 건널목 직전 정지 및 안전 확인을 원칙으로 하고, 신호에 따르는 경우의 예외를 정한다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:5' => [
            'en' => [
                'question_text' => 'Even while the railroad-crossing alarm is sounding, you may enter the railroad crossing (踏切) if no train is visible.',
                'explanation' => 'False. Road Traffic Act Article 33(2) prohibits entering a railroad crossing while the barrier is closing or closed, or while the alarm is sounding. Whether a train is visible is not a condition that permits entry.',
            ],
            'zh' => [
                'question_text' => '即使铁路道口（踏切）警报器正在报警，只要看不见列车，也可以进入道口。',
                'explanation' => '错误。道路交通法第33条第2项规定，遮断机正在关闭或已关闭期间，或者警报器正在报警期间，不得进入道口。能否看见列车并不是允许进入的条件。',
            ],
            'vi' => [
                'question_text' => 'Ngay cả khi chuông báo đường cắt đường sắt (踏切) đang kêu, vẫn được vào đường cắt nếu không nhìn thấy tàu.',
                'explanation' => 'Sai. Điều 33 khoản 2 Luật Giao thông đường bộ Nhật Bản cấm vào đường cắt khi chắn đang đóng/đóng lại hoặc khi chuông báo đang kêu. Việc có nhìn thấy tàu hay không không phải điều kiện cho phép vào.',
            ],
            'fil' => [
                'question_text' => 'Kahit tumutunog ang alarm ng tawiran ng riles (踏切), maaari kang pumasok sa tawiran kung walang nakikitang tren.',
                'explanation' => 'Mali. Ipinagbabawal ng Artikulo 33(2) ng Road Traffic Act ang pagpasok sa tawiran habang nagsasara o nakasara ang hadlang, o habang tumutunog ang alarm. Ang pagkakita o hindi sa tren ay hindi kondisyon para payagan ang pagpasok.',
            ],
            'ne' => [
                'question_text' => 'रेल क्रसिङ (踏切) को अलार्म बजिरहेको बेला पनि, रेल नदेखिएमा क्रसिङमा प्रवेश गर्न हुन्छ।',
                'explanation' => 'गलत। सडक यातायात ऐन दफा ३३(२) ले अवरोधक बन्द हुँदै/बन्द हुँदा वा अलार्म बज्दा क्रसिङमा प्रवेश निषेध गर्छ। रेल देखिने/नदेखिने प्रवेश अनुमति होइन।',
            ],
            'bn' => [
                'question_text' => 'রেল ক্রসিং (踏切) এর অ্যালার্ম বাজতে থাকলেও, ট্রেন না দেখা গেলে ক্রসিংয়ে প্রবেশ করা যায়।',
                'explanation' => 'ভুল। সড়ক যানবাহন আইন ধারা ৩৩(২) ব্যারিয়ার বন্ধ হচ্ছে/বন্ধ আছে বা অ্যালার্ম বাজছে এমন সময়ে ক্রসিংয়ে প্রবেশ নিষেধ করে। ট্রেন দেখা যাচ্ছে কিনা প্রবেশের শর্ত নয়।',
            ],
            'ko' => [
                'question_text' => '건널목(踏切)의 경보기가 경보하고 있는 동안에도, 열차가 보이지 않으면 건널목에 진입해도 된다.',
                'explanation' => '틀렸다. 도로교통법 제33조 제2항은 차단기가 닫히려고 하거나 닫혀 있는 동안, 또는 경보기가 경보하고 있는 동안 건널목에 들어가서는 안 된다고 정한다. 열차가 보이는지는 진입 가능 조건이 아니다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:6' => [
            'en' => [
                'question_text' => 'On a road where the maximum speed (最高速度) is designated by a road sign or marking, a vehicle must not proceed at a speed exceeding that maximum speed.',
                'explanation' => 'Road Traffic Act Article 22(1) prohibits exceeding a posted maximum speed. The maximum is an upper limit; depending on conditions, a lower speed may still be required (see also the duty of safe driving).',
            ],
            'zh' => [
                'question_text' => '在道路标志等指定了最高速度（最高速度）的道路上，车辆不得超过该最高速度行驶。',
                'explanation' => '道路交通法第22条第1项规定不得以超过标志等指定最高速度的速度行驶。最高速度是上限；视情况仍可能需要更低速度（并参见安全驾驶义务）。',
            ],
            'vi' => [
                'question_text' => 'Trên đường có tốc độ tối đa (最高速度) được chỉ định bằng biển báo/đánh dấu, phương tiện không được chạy vượt quá tốc độ tối đa đó.',
                'explanation' => 'Điều 22 khoản 1 Luật Giao thông đường bộ Nhật Bản cấm vượt tốc độ tối đa đã chỉ định. Đó là giới hạn trên; tùy tình huống vẫn có thể cần chạy chậm hơn (xem thêm nghĩa vụ lái xe an toàn).',
            ],
            'fil' => [
                'question_text' => 'Sa daan kung saan ang pinakamataas na bilis (最高速度) ay itinalaga ng palatandaan sa daan, ang sasakyan ay hindi dapat tumakbo nang lampas sa bilis na iyon.',
                'explanation' => 'Ipinagbabawal ng Artikulo 22(1) ng Road Traffic Act ang paglampas sa naka-post na maximum speed. Ito ay upper limit; maaaring kailanganin pa rin ang mas mababang bilis ayon sa sitwasyon (tingnan din ang tungkulin ng ligtas na pagmamaneho).',
            ],
            'ne' => [
                'question_text' => 'सडक चिन्ह आदिले अधिकतम गति (最高速度) तोकिएको सडकमा, सवारीले उक्त अधिकतम गति नाघेर चलाउनु हुँदैन।',
                'explanation' => 'सडक यातायात ऐन दफा २२(१) ले तोकिएको अधिकतम गति नाघ्न निषेध गर्छ। यो माथिल्लो सीमा हो; अवस्थाअनुसार अझ कम गति चाहिन सक्छ (सुरक्षित ड्राइभिङ कर्तव्य पनि हेर्नुहोस्)।',
            ],
            'bn' => [
                'question_text' => 'সড়ক চিহ্ন ইত্যাদি দ্বারা সর্বোচ্চ গতি (最高速度) নির্ধারিত সড়কে, যানবাহন সেই সর্বোচ্চ গতি অতিক্রম করে চলতে পারবে না।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ২২(১) নির্ধারিত সর্বোচ্চ গতি অতিক্রম নিষেধ করে। এটি উপরের সীমা; পরিস্থিতি অনুযায়ী আরও কম গতি প্রয়োজন হতে পারে (নিরাপদ চালনার দায়িত্বও দেখুন)।',
            ],
            'ko' => [
                'question_text' => '도로표지 등에 의해 최고속도(最高速度)가 지정되어 있는 도로에서는, 차량은 그 최고속도를 넘는 속도로 진행해서는 안 된다.',
                'explanation' => '도로교통법 제22조 제1항은 표지 등으로 지정된 최고속도를 넘어 진행해서는 안 된다고 정한다. 최고속도는 상한이며, 상황에 따라 더 낮은 속도가 필요할 수 있다(안전운전 의무도 참조).',
            ],
        ],
        'menkyo_original_karimen_pilot_001:7' => [
            'en' => [
                'question_text' => 'A driver of a vehicle must operate the steering wheel, brakes, and other devices reliably, and must drive at a speed and in a manner that does not endanger others, according to road, traffic, and vehicle conditions (duty of safe driving / 安全運転義務).',
                'explanation' => 'Road Traffic Act Article 70 sets the duty of safe driving. Even within a speed limit, you must drive at a safe speed and in a safe manner for the conditions.',
            ],
            'zh' => [
                'question_text' => '车辆等的驾驶人必须切实操作方向盘、制动器及其他装置，并根据道路、交通及该车辆等的状况，以不危害他人的速度和方法驾驶（安全驾驶义务／安全運転義務）。',
                'explanation' => '道路交通法第70条规定安全驾驶义务。即使在限制速度以内，也必须按状况以安全的速度和方法驾驶。',
            ],
            'vi' => [
                'question_text' => 'Người lái phương tiện phải thao tác vô-lăng, phanh và các thiết bị khác một cách chắc chắn, và phải lái với tốc độ cùng cách thức không gây hại cho người khác, phù hợp với tình trạng đường, giao thông và phương tiện (nghĩa vụ lái xe an toàn / 安全運転義務).',
                'explanation' => 'Điều 70 Luật Giao thông đường bộ Nhật Bản quy định nghĩa vụ lái xe an toàn. Ngay cả trong giới hạn tốc độ, vẫn phải lái với tốc độ và cách thức an toàn theo tình huống.',
            ],
            'fil' => [
                'question_text' => 'Ang drayber ng sasakyan ay dapat tiyak na gamitin ang manibela, preno, at iba pang kagamitan, at magmaneho sa bilis at paraang hindi magdudulot ng panganib sa iba, ayon sa kalagayan ng daan, trapiko, at sasakyan (tungkulin ng ligtas na pagmamaneho / 安全運転義務).',
                'explanation' => 'Itinatakda ng Artikulo 70 ng Road Traffic Act ang tungkulin ng ligtas na pagmamaneho. Kahit nasa loob ng speed limit, kailangan pa ring magmaneho nang ligtas ayon sa sitwasyon.',
            ],
            'ne' => [
                'question_text' => 'सवारी चालकले स्टेयरिङ, ब्रेक र अन्य उपकरण निश्चित रूपमा चलाउनुपर्छ, र सडक, ट्राफिक तथा सवारीको अवस्थाअनुसार अरूलाई हानि नपुग्ने गति र तरिकाले चलाउनुपर्छ (सुरक्षित ड्राइभिङ कर्तव्य／安全運転義務)।',
                'explanation' => 'सडक यातायात ऐन दफा ७० ले सुरक्षित ड्राइभिङ कर्तव्य तोक्छ। गति सीमाभित्रै भए पनि अवस्थाअनुसार सुरक्षित गति र तरिका आवश्यक छ।',
            ],
            'bn' => [
                'question_text' => 'যানবাহনের চালককে স্টিয়ারিং, ব্রেক ও অন্যান্য যন্ত্র নির্ভরযোগ্যভাবে পরিচালনা করতে হবে, এবং সড়ক, যানবাহন ও পরিস্থিতি অনুযায়ী অন্যকে ক্ষতি না করে এমন গতি ও পদ্ধতিতে চালাতে হবে (নিরাপদ চালনার দায়িত্ব／安全運転義務)।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ৭০ নিরাপদ চালনার দায়িত্ব নির্ধারণ করে। গতিসীমার মধ্যে থাকলেও পরিস্থিতি অনুযায়ী নিরাপদ গতি ও পদ্ধতি প্রয়োজন।',
            ],
            'ko' => [
                'question_text' => '차량 등의 운전자는 해당 차량 등의 핸들, 브레이크 그 밖의 장치를 확실하게 조작하고, 또한 도로·교통 및 해당 차량 등의 상황에 따라 타인에게 위해를 끼치지 않는 속도와 방법으로 운전해야 한다(안전운전 의무／安全運転義務).',
                'explanation' => '도로교통법 제70조는 안전운전의 의무를 정한다. 제한속도 이내라도 상황에 따른 안전한 속도와 방법으로 운전해야 한다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:9' => [
            'en' => [
                'question_text' => 'When a vehicle turns right at an intersection (交差点), if there is a vehicle going straight or turning left at that intersection, it must not impede the progress of that vehicle.',
                'explanation' => 'Road Traffic Act Article 37 provides that a vehicle turning right must not impede vehicles that are going straight or turning left at the intersection.',
            ],
            'zh' => [
                'question_text' => '车辆等在交叉路口（交差点）右转时，如该交叉路口有直行或左转的车辆等，则不得妨碍该车辆等的前进。',
                'explanation' => '道路交通法第37条规定，右转的车辆等不得妨碍在该交叉路口直行或左转的车辆等的前进。',
            ],
            'vi' => [
                'question_text' => 'Khi phương tiện rẽ phải tại giao lộ (交差点), nếu có phương tiện đang đi thẳng hoặc rẽ trái tại giao lộ đó thì không được cản trở việc đi của phương tiện đó.',
                'explanation' => 'Điều 37 Luật Giao thông đường bộ Nhật Bản quy định phương tiện rẽ phải không được cản trở phương tiện đi thẳng hoặc rẽ trái tại giao lộ.',
            ],
            'fil' => [
                'question_text' => 'Kapag ang sasakyan ay kumanan sa interseksyon (交差点), kung may sasakyang dumidiretso o kumakaliwa sa interseksyong iyon, hindi nito dapat hadlangan ang pag-usad ng sasakyang iyon.',
                'explanation' => 'Ayon sa Artikulo 37 ng Road Traffic Act, ang sasakyang kumanan ay hindi dapat hadlangan ang mga sasakyang dumidiretso o kumakaliwa sa interseksyon.',
            ],
            'ne' => [
                'question_text' => 'सवारी चौबाटो (交差点) मा दायाँ मोड्दा, उक्त चौबाटोमा सिधा जाने वा बायाँ मोड्ने सवारी भएमा, ती सवारीको प्रगतिमा बाधा पार्नु हुँदैन।',
                'explanation' => 'सडक यातायात ऐन दफा ३७ ले दायाँ मोड्ने सवारीले सिधा जाने वा बायाँ मोड्ने सवारीमा बाधा पार्न नहुने तोक्छ।',
            ],
            'bn' => [
                'question_text' => 'যানবাহন সংযোগস্থলে (交差点) ডানদিকে মোড় নিলে, সেই সংযোগস্থলে সোজা যাওয়া বা বাঁদিকে মোড় নেওয়া যানবাহন থাকলে, সেই যানবাহনের অগ্রগমনে বাধা দেওয়া যাবে না।',
                'explanation' => 'সড়ক যানবাহন আইন ধারা ৩৭ বলে, ডান মোড় নেওয়া যানবাহন সংযোগস্থলে সোজা যাওয়া বা বাঁ মোড় নেওয়া যানবাহনে বাধা দিতে পারবে না।',
            ],
            'ko' => [
                'question_text' => '차량 등은 교차로(交差点)에서 우회전하는 경우, 해당 교차로에서 직진하거나 좌회전하려고 하는 차량 등이 있을 때에는 해당 차량 등의 진행을 방해해서는 안 된다.',
                'explanation' => '도로교통법 제37조는 우회전하는 차량 등이 직진 또는 좌회전하려는 차량 등의 진행을 방해해서는 안 된다고 정한다.',
            ],
        ],
        'menkyo_original_karimen_pilot_001:10' => [
            'en' => [
                'question_text' => 'At a place where temporary stop (一時停止) is designated by a road sign or marking, slow travel (徐行) is enough and a temporary stop is unnecessary.',
                'explanation' => 'False. Road Traffic Act Article 43 requires a temporary stop. Slow travel under Article 42 is a different duty and does not replace a temporary stop.',
            ],
            'zh' => [
                'question_text' => '在道路标志等指定应当临时停车（一時停止）的场所，只要缓行（徐行）即可，不必临时停车。',
                'explanation' => '错误。道路交通法第43条要求临时停车。第42条的缓行是另一项义务，不能代替临时停车。',
            ],
            'vi' => [
                'question_text' => 'Tại nơi biển báo/đánh dấu chỉ định phải dừng tạm thời (一時停止), chỉ cần đi chậm (徐行) là đủ và không cần dừng tạm thời.',
                'explanation' => 'Sai. Điều 43 Luật Giao thông đường bộ Nhật Bản yêu cầu dừng tạm thời. Đi chậm theo Điều 42 là nghĩa vụ khác và không thay thế việc dừng tạm thời.',
            ],
            'fil' => [
                'question_text' => 'Sa lugar kung saan ang pansamantalang paghinto (一時停止) ay itinalaga ng palatandaan sa daan, sapat na ang mabagal na pagpatuloy (徐行) at hindi na kailangan ng pansamantalang paghinto.',
                'explanation' => 'Mali. Nangangailangan ang Artikulo 43 ng Road Traffic Act ng pansamantalang paghinto. Ang mabagal na pagpatuloy sa ilalim ng Artikulo 42 ay ibang tungkulin at hindi pumapalit sa pansamantalang paghinto.',
            ],
            'ne' => [
                'question_text' => 'सडक चिन्ह आदिले अस्थायी रोक (一時停止) तोकिएको स्थानमा, बिस्तारै चलाए (徐行) पुग्छ र अस्थायी रोक आवश्यक छैन।',
                'explanation' => 'गलत। सडक यातायात ऐन दफा ४३ ले अस्थायी रोक माग्छ। दफा ४२ को बिस्तारै चलाउने अर्को कर्तव्य हो र अस्थायी रोकको विकल्प होइन।',
            ],
            'bn' => [
                'question_text' => 'সড়ক চিহ্ন ইত্যাদি দ্বারা সাময়িক থামা (一時停止) নির্ধারিত স্থানে, ধীরে চলা (徐行) যথেষ্ট এবং সাময়িক থামা প্রয়োজন নেই।',
                'explanation' => 'ভুল। সড়ক যানবাহন আইন ধারা ৪৩ সাময়িক থামা দাবি করে। ধারা ৪২-এর ধীরে চলা আলাদা বাধ্যবাধকতা এবং সাময়িক থামার বিকল্প নয়।',
            ],
            'ko' => [
                'question_text' => '도로표지 등에 의해 일시정지(一時停止)해야 할 것이 지정되어 있는 장소에서는, 서행(徐行)하면 일시정지하지 않아도 된다.',
                'explanation' => '틀렸다. 도로교통법 제43조는 일시정지를 요구한다. 서행(제42조)은 별개의 의무이며 일시정지를 대신하지 않는다.',
            ],
        ],
    ],
];
