
# show break down of naturalness scoring

SELECT calc_naturalness, count(*) FROM `image` 
where path like '%SV_%'
group by calc_naturalness order by calc_naturalness 

SELECT calc_artificialness, count(*) FROM `image` 
where path like '%SV_%'
group by calc_artificialness order by calc_artificialness 

# summarise the naturalness scores
SELECT image_id, min(naturalness) as evaluation_min, avg(naturalness) as evaluation_avg, max(naturalness) as evaluation_max, std(naturalness) as evaluation_std
FROM `image_evaluation`
group by image_id
order by std desc 

UPDATE images as i 
JOIN 
    (
       SELECT image_id, min(naturalness) as evaluation_min, avg(naturalness) as evaluation_avg, max(naturalness) as evaluation_max, std(naturalness) as evaluation_std
       FROM `image_evaluation`
       GROUP BY image_id 
    ) as e on i.id = e.image_id
SET i.evaluation_avg = e.evaluation_avg,
i.evaluation_min = e.evaluation_min,
i.evaluation_max = e.evaluation_max,
i.evaluation_sd = e.evaluation_std

