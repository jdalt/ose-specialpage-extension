--
-- SQL schema for TrueFanStories extension
--

CREATE TABLE /*_*/true_fans (
  tf_id int(9) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  tf_foreign_id varchar(15) NOT NULL UNIQUE,
  tf_name varchar(255) NOT NULL,
  tf_email varchar(255) NOT NULL,
  tf_video_id varchar(255) NOT NULL,
  tf_video_message text,
  tf_email_invite_list text
) /*$wgDBTableOptions*/;