import unittest2
from e2e.base_testcase import BaseTestCase


class TestNavigationPageNonStudent(BaseTestCase):
    def setUp(self):
        self.driver = BaseTestCase.DRIVER

    def test_instructor(self):
        self.log_in(user_id="instructor", user_password="instructor", user_name="Instructor")
        elements = self.driver.find_elements_by_class_name('nav-title-row')
        self.assertEqual(5, len(elements))
        self.assertEqual("future", elements[0].get_attribute('id'))
        self.assertEqual(6, len(self.driver
                         .find_element_by_id('future_tbody')
                         .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("open", elements[1].get_attribute('id'))
        self.assertEqual(1, len(self.driver
                         .find_element_by_id('open_tbody')
                         .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("closed", elements[2].get_attribute('id'))
        self.assertEqual(1, len(self.driver
                         .find_element_by_id('closed_tbody')
                         .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("items_being_graded", elements[3].get_attribute('id'))
        self.assertEqual(3, len(self.driver
                         .find_element_by_id('items_being_graded_tbody')
                         .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("graded", elements[4].get_attribute('id'))
        self.assertEqual(3, len(self.driver
                         .find_element_by_id('graded_tbody')
                         .find_elements_by_class_name("gradeable_row")))
        self.assertEqual(4, len(self.driver.find_element_by_class_name(
            'gradeable_row').find_elements_by_tag_name('td')))

    def test_ta(self):
        self.log_in(user_id="ta", user_password="ta", user_name="TA")
        elements = self.driver.find_elements_by_class_name('nav-title-row')
        self.assertEqual(5, len(elements))
        self.assertEqual("future", elements[0].get_attribute('id'))
        self.assertEqual(3, len(self.driver
                                .find_element_by_id('future_tbody')
                                .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("open", elements[1].get_attribute('id'))
        self.assertEqual(1, len(self.driver
                                .find_element_by_id('open_tbody')
                                .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("closed", elements[2].get_attribute('id'))
        self.assertEqual(1, len(self.driver
                                .find_element_by_id('closed_tbody')
                                .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("items_being_graded", elements[3].get_attribute('id'))
        self.assertEqual(3, len(self.driver
                                .find_element_by_id('items_being_graded_tbody')
                                .find_elements_by_class_name("gradeable_row")))
        self.assertEqual("graded", elements[4].get_attribute('id'))
        self.assertEqual(3, len(self.driver
                                .find_element_by_id('graded_tbody')
                                .find_elements_by_class_name("gradeable_row")))

        self.assertEqual(4, len(self.driver.find_element_by_class_name(
            'gradeable_row').find_elements_by_tag_name('td')))


if __name__ == "__main__":
    unittest2.main()
